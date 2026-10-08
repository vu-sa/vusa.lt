<?php

namespace App\Support\Media;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;

/**
 * The one set of derived images every image collection gets. srcset is built from these widths
 * instead of withResponsiveImages(), which cost ~1.6× the originals on disk for Calendar alone.
 */
final class ImageConversions
{
    public const int ORIGINAL_MAX = 2400;

    /** @var array<string, int> */
    public const array WIDTHS = [
        'thumb' => 400,
        'medium' => 900,
        'large' => 1600,
    ];

    /** @var list<string> */
    public const array MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function register(HasMedia $model): void
    {
        foreach (self::WIDTHS as $name => $width) {
            $model->addMediaConversion($name)
                ->fit(Fit::Max, $width, 10000)
                ->format('webp')
                ->quality(80);
        }
    }
}
