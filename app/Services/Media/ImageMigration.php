<?php

namespace App\Services\Media;

use App\Contracts\ImageMediaOwner;
use App\Jobs\Media\BackfillLegacyImagesJob;
use App\Jobs\Media\ReindexImageSearchablesJob;
use App\Jobs\Media\UpdateImageSearchSchema;
use App\Jobs\Media\UpgradeImageMedia;
use App\Models\PendingUpload;
use App\Support\Media\ImageConversions;
use App\Support\MorphMap;
use App\Support\StagingProtection;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * The steps behind media:migrate-images: bring existing media up to the current ImageConversions
 * policy, then copy legacy image columns into media (LegacyImageBackfill), then reindex once.
 *
 * Transitional(legacy-images): remove when media:migrate-images --status shows nothing pending or superseded.
 */
class ImageMigration
{
    public const int CHUNK = 25;

    public function __construct(private readonly LegacyImageBackfill $legacy) {}

    /** @return Builder<Media> */
    public function ownedMedia(): Builder
    {
        $owners = collect(MorphMap::MAP)
            ->filter(fn (string $class): bool => is_subclass_of($class, ImageMediaOwner::class) && $class !== PendingUpload::class)
            ->keys()->all();

        return Media::query()->whereIn('model_type', $owners)->whereIn('mime_type', ImageConversions::MIME_TYPES);
    }

    /** @return Builder<Media> */
    public function pendingMedia(): Builder
    {
        return $this->ownedMedia()->where(function (Builder $query): void {
            $query->whereNull('custom_properties->image_policy')
                ->orWhere('custom_properties->image_policy', '<', ImageConversions::VERSION);
            foreach (array_keys(ImageConversions::WIDTHS) as $conversion) {
                $query->orWhereNull("generated_conversions->{$conversion}")
                    ->orWhere("generated_conversions->{$conversion}", false);
            }
        });
    }

    /**
     * Newest first, so a limited run covers the images people are most likely to look at.
     *
     * @return list<int>
     */
    public function pendingMediaIds(?int $limit = null): array
    {
        return $this->pendingMedia()->when($limit !== null, fn (Builder $query) => $query->orderByDesc('id')->limit($limit))->pluck('id')->all();
    }

    /** @return Builder<Media> */
    public function supersededMedia(): Builder
    {
        return Media::query()->whereNotNull('custom_properties->superseded_file');
    }

    /**
     * One batch: existing media first (FIFO on the single long-running worker), then the legacy
     * imports. A failing job does not stop the rest; the reindex runs once at the end either way.
     *
     * @param  array{upgrade: list<int>, imports: array<string, list<BackfillLegacyImagesJob>>}  $plan
     */
    public function dispatch(array $plan): ?Batch
    {
        $jobs = [
            ...array_map(fn (array $ids): UpgradeImageMedia => new UpgradeImageMedia($ids), array_chunk($plan['upgrade'], self::CHUNK)),
            ...array_merge([], ...array_values($plan['imports'])),
        ];

        if ($jobs === []) {
            return null;
        }

        UpdateImageSearchSchema::dispatchSync();

        return Bus::batch($jobs)
            ->name('media:migrate-images')
            ->allowFailures()
            ->onConnection('long-running')
            ->finally(fn () => dispatch(new ReindexImageSearchablesJob))
            ->dispatch();
    }

    /**
     * @param  ?string  $onlyTarget  a legacy target; existing media are skipped when one is given
     * @return array{upgrade: list<int>, imports: array<string, list<BackfillLegacyImagesJob>>}
     */
    public function plan(?string $onlyTarget, ?int $limit): array
    {
        return [
            'upgrade' => $onlyTarget === null ? $this->pendingMediaIds($limit) : [],
            'imports' => $this->legacy->importJobs($onlyTarget, $limit),
        ];
    }

    /** @param  callable(int): void  $advance  called with the number of media just processed */
    public function upgradeNow(array $ids, callable $advance): void
    {
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            app()->call([new UpgradeImageMedia($chunk), 'handle']);
            $advance(count($chunk));
        }
    }

    /**
     * Deletes what an upgrade replaced (the old master, stale conversions, responsive images),
     * only once the current master and every current conversion exist.
     */
    public function cleanup(Media $media): bool
    {
        $old = $media->getCustomProperty('superseded_file');
        $disk = Storage::disk($media->disk);
        $conversionsDisk = Storage::disk($media->conversions_disk);

        if (! is_string($old) || $old === $media->file_name || basename($old) !== $old
            || ! StagingProtection::diskIsWritable($media->disk) || ! StagingProtection::diskIsWritable($media->conversions_disk)
            || ! UpgradeImageMedia::isCurrent($media) || ! $disk->exists($media->getPathRelativeToRoot())
            || ! collect(array_keys(ImageConversions::WIDTHS))->every(fn (string $name): bool => $conversionsDisk->exists($media->getPathRelativeToRoot($name)))) {
            return false;
        }

        $paths = PathGeneratorFactory::create($media);
        $current = ConversionCollection::createForMedia($media)->getConversionsFiles($media->collection_name);

        $disk->delete(dirname($media->getPathRelativeToRoot()).'/'.$old);
        foreach ($conversionsDisk->files($paths->getPathForConversions($media)) as $file) {
            if (! $current->contains(basename($file))) {
                $conversionsDisk->delete($file);
            }
        }
        $conversionsDisk->deleteDirectory($paths->getPathForResponsiveImages($media));

        $media->forgetCustomProperty('superseded_file')->saveQuietly();

        return true;
    }

    public function legacy(): LegacyImageBackfill
    {
        return $this->legacy;
    }
}
