<?php

namespace App\Services\Media;

use App\Actions\Media\AddImageMedia;
use App\Contracts\ImageMediaOwner;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Copies a legacy image file into a record's collection. The source file is never moved or
 * deleted: the old folders stay until media:legacy-files confirms nothing references them.
 *
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
class LegacyImageImporter
{
    public const string EXISTING = 'existing';

    public function __construct(
        private readonly LegacyImageResolver $resolver,
        private readonly AddImageMedia $addImage,
    ) {}

    /**
     * @return array{outcome: string, media: Media|null}
     */
    public function import(Model&ImageMediaOwner $owner, string $collection): array
    {
        $existing = $owner->getFirstMedia($collection);

        if ($existing !== null) {
            return ['outcome' => self::EXISTING, 'media' => $existing];
        }

        $columns = $owner->legacyImageColumns()[$collection] ?? null;

        if ($columns === null) {
            return ['outcome' => LegacyImageSource::EMPTY, 'media' => null];
        }

        $value = $owner->getAttribute($columns['url']);
        $source = $this->resolver->resolve(is_string($value) ? $value : null);

        if (! $source->isResolved()) {
            return ['outcome' => $source->outcome, 'media' => null];
        }

        $media = $this->addImage->execute(
            $owner,
            (string) file_get_contents((string) $source->path),
            $collection,
            pathinfo((string) $source->path, PATHINFO_FILENAME),
            array_filter([
                'legacy_source' => $value,
                'focal_point' => isset($columns['focal']) ? $owner->getAttribute($columns['focal']) : null,
                'author' => isset($columns['author']) ? $owner->getAttribute($columns['author']) : null,
            ], fn (mixed $property): bool => $property !== null && $property !== ''),
        );

        $owner->unsetRelation('media');

        // Caches must stop pointing at the legacy file, which media:legacy-files will delete.
        $owner->refreshImageCache($collection);

        return ['outcome' => LegacyImageSource::RESOLVED, 'media' => $media];
    }

    public function importCollection(Model&ImageMediaOwner $owner, string $collection): ?Media
    {
        return $this->import($owner, $collection)['media'];
    }
}
