<?php

namespace App\Support\Media;

use Dom\HTMLDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use Normalizer;

/**
 * File-manager and legacy images under `/uploads/`, which have no media-library conversions. The
 * `/img/{width}/{path}` route serves them scaled down so CMS images get a srcset too.
 *
 * Mirrored by `cmsSrcset()` in resources/js/Types/media.ts.
 */
final class ResizableImage
{
    /** @var list<int> */
    public const array WIDTHS = [400, 900, 1600, 2400, 3840];

    /** The path after `/uploads/` for a same-site image URL, e.g. `files/padaliniai/photo.jpg`. */
    public static function pathFromUrl(string $url): ?string
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5);

        try {
            $uri = Uri::of($url);
        } catch (\Throwable) {
            return null;
        }

        $host = $uri->host();
        if ($host && $host !== Uri::of((string) config('app.url'))->host() && preg_match('/(^|\.)vusa\.(lt|test)$/i', $host) !== 1) {
            return null;
        }

        $path = '/'.ltrim(rawurldecode((string) $uri->path()), '/');

        return str_starts_with($path, '/uploads/') ? self::validPath(substr($path, 9)) : null;
    }

    public static function validPath(string $path): ?string
    {
        if (preg_match('/\.(jpe?g|png|webp)$/i', $path) !== 1
            || preg_match('#(^|/)\.\.?(/|$)#', $path) === 1 || str_contains($path, '\\') || str_contains($path, "\0")
            || preg_match('#^(media|staging-media)/#', $path) === 1) {
            return null;
        }

        return Normalizer::normalize($path, Normalizer::FORM_C) ?: $path;
    }

    /** The stored file `/uploads/<path>` serves: `public/<path>`, else the `files/` alias RewriteUploadsUrl redirects to. */
    public static function storagePath(string $path): ?string
    {
        $path = self::validPath($path);

        if ($path === null) {
            return null;
        }

        foreach (array_unique(['public/'.$path, 'public/files/'.$path]) as $candidate) {
            if (Storage::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** Adds srcset/sizes to resizable `<img>`s, leaving all other markup as authored. */
    public static function withSrcset(string $html): string
    {
        if (stripos($html, '<img') === false) {
            return $html;
        }

        $document = HTMLDocument::createFromString('<!DOCTYPE html><body>'.$html.'</body>', LIBXML_NOERROR);
        $changed = false;

        foreach ($document->body->querySelectorAll('img[src]') as $img) {
            $path = self::pathFromUrl($img->getAttribute('src'));
            if ($path === null || $img->hasAttribute('srcset')) {
                continue;
            }

            $width = $img->getAttribute('width');
            $img->setAttribute('srcset', self::srcset($path));
            $img->setAttribute('sizes', is_numeric($width) ? "min({$width}px, 100vw)" : '(min-width: 1024px) 928px, 100vw');
            $changed = true;
        }

        return $changed ? $document->body->innerHTML : $html;
    }

    public static function srcset(string $path): string
    {
        return collect(self::WIDTHS)
            ->map(fn (int $width): string => self::url($path, $width).' '.$width.'w')
            ->implode(', ');
    }

    public static function url(string $path, int $width): string
    {
        return '/img/'.$width.'/'.implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }
}
