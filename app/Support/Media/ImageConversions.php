<?php

namespace App\Support\Media;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The one set of derived images every image collection gets. srcset is built from these widths
 * instead of withResponsiveImages(), which cost ~1.6× the originals on disk for Calendar alone.
 */
final class ImageConversions
{
    public const int ORIGINAL_MAX = 3840;

    /** @var array<string, int> */
    public const array WIDTHS = [
        'thumb' => 400,
        'medium' => 900,
        'large' => 1600,
        'wide' => 2400,
    ];

    /** @var list<string> */
    public const array MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const int VERSION = 2;

    public const int MASTER_QUALITY = 90;

    public const int CONVERSION_QUALITY = 86;

    /** @var list<string> Logos and support screenshots: lettering blurs under lossy encoding. */
    public const array ARTWORK_COLLECTIONS = ['logo', 'evidence'];

    /**
     * Artwork is encoded losslessly (WebP quality 100). Photos stay lossy even as PNG: a lossless
     * 3840px photo is the top srcset candidate and would cost 10+ MB.
     */
    public static function isArtwork(string $contents, ?string $collection = null): bool
    {
        if (in_array($collection, self::ARTWORK_COLLECTIONS, true)) {
            return true;
        }

        if (str_starts_with($contents, "\x89PNG")) {
            // IHDR colour type 4/6 carries alpha; a tRNS chunk makes a palette transparent.
            return in_array(ord($contents[25] ?? "\0"), [4, 6], true) || str_contains(substr($contents, 0, 65536), 'tRNS');
        }

        if (str_starts_with($contents, 'RIFF') && substr($contents, 8, 4) === 'WEBP') {
            $chunk = substr($contents, 12, 4);

            return $chunk === 'VP8L' || ($chunk === 'VP8X' && (ord($contents[20] ?? "\0") & 0x10) !== 0);
        }

        return false;
    }

    public static function register(HasMedia $model, ?Media $media = null): void
    {
        foreach (self::WIDTHS as $name => $width) {
            // The server's cwebp optimizer would re-encode this at -q 90: bigger and slower. Called
            // first: the forwarded manipulations below are typed as ImageDriver, not Conversion.
            $model->addMediaConversion($name)
                ->nonOptimized()
                ->fit(Fit::Max, $width)
                ->format('webp')
                ->quality($media?->getCustomProperty('lossless') ? 100 : self::CONVERSION_QUALITY);
        }
    }
}
