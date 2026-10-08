<?php

namespace App\Actions\Media;

use App\Contracts\ImageMediaOwner;
use App\Models\PendingUpload;
use App\Models\User;
use App\Services\Media\LegacyImageImporter;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Applies an ImageData object posted by a form to one image collection of a record.
 *
 * A staged upload is moved onto the record by reassigning its media row. Media::move() would copy
 * the file under a new id and regenerate every conversion; files are keyed by media id, so
 * reassigning keeps them and their conversions valid.
 */
class SyncImageMedia
{
    private const array PROPERTIES = ['focal_point', 'alt', 'author'];

    public function __construct(private readonly LegacyImageImporter $legacyImporter) {}

    /**
     * @param  array<string, mixed>|null  $ref  null removes the image
     */
    public function execute(Model&ImageMediaOwner $owner, string $collection, ?array $ref, User $actor): void
    {
        $changed = DB::transaction(fn (): bool => $this->apply($owner, $collection, $ref, $actor));

        $owner->unsetRelation('media');

        if ($changed) {
            DB::afterCommit(function () use ($owner, $collection): void {
                $owner->refreshImageCache($collection);
                $owner->afterImageMediaChanged($collection);
            });
        }
    }

    /**
     * For a request key that may be absent: absent leaves the image as it is.
     *
     * @param  array<string, mixed>  $validated
     */
    public function fromValidated(Model&ImageMediaOwner $owner, string $collection, array $validated, string $key, User $actor): void
    {
        if (array_key_exists($key, $validated)) {
            $this->execute($owner, $collection, is_array($validated[$key]) ? $validated[$key] : null, $actor);
        }
    }

    public static function ownedMedia(Model&ImageMediaOwner $owner, string $collection, int $mediaId): ?Media
    {
        return Media::query()
            ->whereKey($mediaId)
            ->where('model_type', $owner->getMorphClass())
            ->where('model_id', $owner->getKey())
            ->where('collection_name', $collection)
            ->first();
    }

    /**
     * Another record's image of the same kind that the actor can see, e.g. the article a
     * translation is copied from. It is copied, never moved.
     */
    public static function copyableMedia(User $actor, Model&ImageMediaOwner $owner, string $collection, int $mediaId): ?Media
    {
        $media = Media::query()
            ->whereKey($mediaId)
            ->where('model_type', $owner->getMorphClass())
            ->where('collection_name', $collection)
            ->with('model')
            ->first();

        return $media?->model !== null && $actor->can('view', $media->model) ? $media : null;
    }

    /**
     * @return Builder<Media>
     */
    public static function stagedMediaQuery(User $actor, int $mediaId): Builder
    {
        return Media::query()
            ->whereKey($mediaId)
            ->where('model_type', MorphMap::alias(PendingUpload::class))
            ->whereIn('model_id', PendingUpload::query()->whereBelongsTo($actor)->select('id'));
    }

    /**
     * @param  array<string, mixed>|null  $ref
     */
    private function apply(Model&ImageMediaOwner $owner, string $collection, ?array $ref, User $actor): bool
    {
        if ($ref === null) {
            $owner->clearMediaCollection($collection);

            return true;
        }

        $mediaId = isset($ref['id']) && is_numeric($ref['id']) ? (int) $ref['id'] : null;

        if ($mediaId === null) {
            return $this->updateCurrent($owner, $collection, $ref);
        }

        $media = self::ownedMedia($owner, $collection, $mediaId);

        if ($media !== null) {
            return $this->applyProperties($media, $ref);
        }

        $media = self::stagedMediaQuery($actor, $mediaId)->lockForUpdate()->first();

        if ($media !== null) {
            $this->attachStaged($owner, $collection, $media, $ref);

            return true;
        }

        $source = self::copyableMedia($actor, $owner, $collection, $mediaId);

        if ($source === null) {
            throw ValidationException::withMessages(['id' => __('validation.exists', ['attribute' => 'id'])]);
        }

        if ($this->isSingleFile($owner, $collection)) {
            $owner->clearMediaCollection($collection);
        }

        $this->applyProperties($source->copy($owner, $collection), $ref);

        return true;
    }

    /**
     * A form that only had the cached URL (avatars on pivots) posts no id: it keeps whatever
     * image the record has and edits its properties.
     *
     * @param  array<string, mixed>  $ref
     */
    private function updateCurrent(Model&ImageMediaOwner $owner, string $collection, array $ref): bool
    {
        // Transitional(legacy-images): drop the import fallback once nothing is pending.
        $media = $owner->getFirstMedia($collection) ?? $this->legacyImporter->importCollection($owner, $collection);

        return $media !== null && $this->applyProperties($media, $ref);
    }

    /**
     * @param  array<string, mixed>  $ref
     */
    private function attachStaged(Model&ImageMediaOwner $owner, string $collection, Media $media, array $ref): void
    {
        $pending = PendingUpload::query()->find($media->model_id);

        if ($this->isSingleFile($owner, $collection)) {
            $owner->getMedia($collection)->each(fn (Media $existing) => $existing->delete());
        }

        $media->model()->associate($owner);
        $media->collection_name = $collection;
        $media->order_column = (int) Media::query()
            ->where('model_type', $owner->getMorphClass())
            ->where('model_id', $owner->getKey())
            ->max('order_column') + 1;
        $this->fillProperties($media, $ref);
        $media->save();

        $pending?->deletePreservingMedia();
    }

    /**
     * @param  array<string, mixed>  $ref
     */
    private function applyProperties(Media $media, array $ref): bool
    {
        $this->fillProperties($media, $ref);

        if (! $media->isDirty()) {
            return false;
        }

        $media->save();

        return true;
    }

    /**
     * @param  array<string, mixed>  $ref
     */
    private function fillProperties(Media $media, array $ref): void
    {
        foreach (self::PROPERTIES as $property) {
            if (! array_key_exists($property, $ref)) {
                continue;
            }

            $value = is_string($ref[$property]) && $ref[$property] !== '' ? $ref[$property] : null;

            if ($value === null) {
                $media->forgetCustomProperty($property);
            } else {
                $media->setCustomProperty($property, $value);
            }
        }
    }

    private function isSingleFile(Model&ImageMediaOwner $owner, string $collection): bool
    {
        return $owner->getMediaCollection($collection)->singleFile ?? false;
    }
}
