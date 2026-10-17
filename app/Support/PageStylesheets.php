<?php

namespace App\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\HtmlString;

/**
 * Stylesheet links for a page's surface entry and component chunk, so `app.blade.php` can load
 * their CSS up front. Their scripts are left out: as extra entry tags they raced app.ts's own
 * dynamic import and sometimes left the CSR-only admin app unmounted.
 */
final class PageStylesheets
{
    /**
     * @param  'public'|'admin'  $surface
     */
    public static function for(string $surface, string $component): HtmlString
    {
        $vite = app(Vite::class);

        // The dev server injects component CSS through JS; there are no files to link yet.
        if ($vite->isRunningHot()) {
            return new HtmlString('');
        }

        try {
            $tags = $vite(["resources/js/{$surface}.ts", "resources/js/Pages/{$component}.vue"])->toHtml();
            $appStylesheet = $vite->asset('resources/css/app.css');
        } catch (ViteException) {
            // A head optimisation must never fail the page; app.ts still loads this CSS.
            return new HtmlString('');
        }

        preg_match_all('/<link rel="stylesheet" href="([^"]+)"[^>]*>/', $tags, $matches, PREG_SET_ORDER);

        $links = collect($matches)
            ->reject(fn (array $match): bool => $match[1] === $appStylesheet)
            ->map(fn (array $match): string => $match[0])
            ->unique();

        return new HtmlString($links->implode(''));
    }
}
