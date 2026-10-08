<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The single shape an image reaches the frontend in (resources/js/Types/media.ts). Forms post the
 * same shape back, so `id` is what the server attaches and the rest is what the user edited.
 *
 * @phpstan-type ImageDataArray array{id: int|null, url: string, thumb: string, srcset: string|null, width: int|null, height: int|null, focal_point: string|null, alt: string|null, author: string|null}
 */
final class ImageData
{
    /**
     * @return ImageDataArray
     */
    public static function fromMedia(Media $media): array
    {
        $width = self::intOrNull($media->getCustomProperty('width'));
        $url = $media->getUrl();

        return [
            'id' => $media->id,
            'url' => $url,
            'thumb' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $url,
            'srcset' => self::srcset($media, $url, $width),
            'width' => $width,
            'height' => self::intOrNull($media->getCustomProperty('height')),
            'focal_point' => self::stringOrNull($media->getCustomProperty('focal_point')),
            'alt' => self::stringOrNull($media->getCustomProperty('alt')),
            'author' => self::stringOrNull($media->getCustomProperty('author')),
        ];
    }

    /**
     * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
     *
     * @return ImageDataArray|null
     */
    public static function fromLegacy(?string $url, ?string $focalPoint = null, ?string $author = null): ?array
    {
        if ($url !== LegacyImageUrl::PLACEHOLDER && preg_match('/^[0-9a-f]{40}\.(jpe?g|png)$/i', $url ?? '') === 1) {
            $url = '/uploads/files/news/'.$url;
        }

        if (! LegacyImageUrl::isDisplayable($url)) {
            return null;
        }

        return [
            'id' => null,
            'url' => (string) $url,
            'thumb' => (string) $url,
            'srcset' => null,
            'width' => null,
            'height' => null,
            'focal_point' => self::stringOrNull($focalPoint),
            'alt' => null,
            'author' => self::stringOrNull($author),
        ];
    }

    /**
     * Only conversions that exist on disk go in, since queued ones may still be pending. A
     * candidate never claims more pixels than the original has.
     */
    private static function srcset(Media $media, string $url, ?int $width): ?string
    {
        $candidates = [];

        foreach (ImageConversions::WIDTHS as $name => $conversionWidth) {
            if (! $media->hasGeneratedConversion($name)) {
                continue;
            }

            $descriptor = $width === null ? $conversionWidth : min($conversionWidth, $width);
            $candidates[$descriptor] ??= $media->getUrl($name);
        }

        if ($width !== null && ! isset($candidates[$width]) && $width > (array_key_last($candidates) ?? 0)) {
            $candidates[$width] = $url;
        }

        if ($candidates === []) {
            return null;
        }

        ksort($candidates);

        return collect($candidates)
            ->map(fn (string $candidateUrl, int $descriptor): string => self::encodeForSrcset($candidateUrl).' '.$descriptor.'w')
            ->implode(', ');
    }

    /**
     * File names keep their spaces and commas, which would split a srcset candidate.
     */
    private static function encodeForSrcset(string $url): string
    {
        return str_replace([' ', ','], ['%20', '%2C'], $url);
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
