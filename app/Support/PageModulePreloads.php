<?php

namespace App\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\HtmlString;

/**
 * Module preloads for a page's surface entry and component chunk. app.ts only imports the
 * surface once it has run, so without these the browser discovers them one hop at a time.
 * Preloads fetch without executing, unlike the entry script tags {@see PageStylesheets} avoids.
 */
final class PageModulePreloads
{
    /**
     * @param  'public'|'admin'  $surface
     */
    public static function for(string $surface, string $component): HtmlString
    {
        $vite = app(Vite::class);

        if ($vite->isRunningHot()) {
            return new HtmlString('');
        }

        try {
            $tags = $vite(["resources/js/{$surface}.ts", "resources/js/Pages/{$component}.vue"])->toHtml();
            $appScript = $vite->asset('resources/js/app.ts');
        } catch (ViteException) {
            return new HtmlString('');
        }

        preg_match_all('/<(?:link rel="modulepreload" href|script type="module" src)="([^"]+)"/', $tags, $matches);

        $links = collect($matches[1])
            ->reject(fn (string $href): bool => $href === $appScript)
            ->unique()
            ->map(fn (string $href): string => '<link rel="modulepreload" href="'.e($href).'">');

        return new HtmlString($links->implode(''));
    }
}
