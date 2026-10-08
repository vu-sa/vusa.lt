<?php

namespace App\Models\Traits;

use App\Support\Media\ImageConversions;
use App\Support\Media\ImageData;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Image collections with the shared conversions and the ImageData shape. Models declare their
 * collections in registerMediaCollections() through registerImageCollection().
 *
 * @phpstan-import-type ImageDataArray from ImageData
 */
trait HasImageMedia
{
    use InteractsWithMedia;

    protected function registerImageCollection(string $name, bool $single = true): MediaCollection
    {
        $collection = $this->addMediaCollection($name)->acceptsMimeTypes(ImageConversions::MIME_TYPES);
        $collection->registerMediaConversions(fn () => ImageConversions::register($this));

        return $single ? $collection->singleFile() : $collection;
    }

    /**
     * @return ImageDataArray|null
     */
    public function imageData(string $collection): ?array
    {
        $media = $this->getFirstMedia($collection);

        return $media !== null ? ImageData::fromMedia($media) : $this->legacyImageData($collection);
    }

    /**
     * @return list<ImageDataArray>
     */
    public function imageGallery(string $collection): array
    {
        return $this->getMedia($collection)
            ->map(fn (Media $media): array => ImageData::fromMedia($media))
            ->values()
            ->all();
    }

    /**
     * Called after a form changed a collection, outside the media transaction. Media writes fire
     * no owner model events, so reindexing and cache flushes hang off this.
     */
    public function afterImageMediaChanged(string $collection): void {}

    /**
     * Called when a finished conversion changed the cached URL.
     */
    public function afterImageCacheRefreshed(string $collection): void {}

    /**
     * Owner columns that mirror a collection for readers that cannot eager-load media, keyed by
     * collection: `url`, the `conversion` whose URL it holds, and optionally `focal` and `author`.
     *
     * @return array<string, array{url: string, conversion: string, focal?: string, author?: string}>
     */
    public function imageCacheColumns(): array
    {
        return [];
    }

    /**
     * Writes the conversion's URL (the original until the conversion exists) and the image's
     * properties into the cache columns. A mirror, not an edit: no events, no updated_at.
     */
    public function refreshImageCache(string $collection): bool
    {
        $columns = $this->imageCacheColumns()[$collection] ?? null;

        if ($columns === null) {
            return false;
        }

        $this->unsetRelation('media');
        $media = $this->getFirstMedia($collection);
        $url = null;

        if ($media !== null) {
            $url = self::relativeImageUrl($media->hasGeneratedConversion($columns['conversion'])
                ? $media->getUrl($columns['conversion'])
                : $media->getUrl());
        }

        $values = [$columns['url'] => $url];

        foreach (['focal' => 'focal_point', 'author' => 'author'] as $key => $property) {
            if (isset($columns[$key])) {
                $value = $media?->getCustomProperty($property);
                $values[$columns[$key]] = is_string($value) && $value !== '' ? $value : null;
            }
        }

        $this->forceFill($values);

        if (! $this->isDirty()) {
            return false;
        }

        static::withoutTimestamps(fn () => $this->saveQuietly());

        return true;
    }

    /**
     * Relative, so a URL cached on production also resolves on staging, where public/uploads
     * links to production's storage.
     */
    private static function relativeImageUrl(string $url): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        return $appUrl !== '' && str_starts_with($url, $appUrl.'/') ? substr($url, strlen($appUrl)) : $url;
    }

    /**
     * The owner columns a collection replaced, keyed by collection: `url`, and optionally `focal`
     * and `author`.
     *
     * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
     *
     * @return array<string, array{url: string, focal?: string, author?: string}>
     */
    public function legacyImageColumns(): array
    {
        return [];
    }

    /**
     * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
     *
     * @return ImageDataArray|null
     */
    public function legacyImageData(string $collection): ?array
    {
        $columns = $this->legacyImageColumns()[$collection] ?? null;

        if ($columns === null) {
            return null;
        }

        return ImageData::fromLegacy(
            $this->stringAttribute($columns['url']),
            isset($columns['focal']) ? $this->stringAttribute($columns['focal']) : null,
            isset($columns['author']) ? $this->stringAttribute($columns['author']) : null,
        );
    }

    private function stringAttribute(string $key): ?string
    {
        $value = $this->getAttribute($key);

        return is_string($value) ? $value : null;
    }
}
