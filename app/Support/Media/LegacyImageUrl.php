<?php

namespace App\Support\Media;

/**
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
final class LegacyImageUrl
{
    /** The old `news.image` column default, a stock photo rather than a chosen image. */
    public const string PLACEHOLDER = '058543019bc51198ea1dc255580d215be99f2297.jpeg';

    /**
     * Whether a browser can load the stored value as it is. Bare file names (old news) and
     * free text (club names in profile_photo_path) cannot.
     */
    public static function isDisplayable(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        return str_starts_with($value, '/') || preg_match('#^https?://#i', $value) === 1;
    }
}
