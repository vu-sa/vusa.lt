<?php

namespace App\Feed;

/**
 * Absolutizes relative URLs in rendered HTML so feed readers (which have no
 * host context) can resolve images and links. Only scheme-relative (//host)
 * and root-relative (/path) URLs are rewritten; absolute URLs pass through.
 */
class FeedHtml
{
    public static function absolutize(string $html): string
    {
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $origin = rtrim(config('app.url'), '/');

        // src="//host/..." -> src="<scheme>://host/..."
        $html = preg_replace_callback(
            '/(src|href)\s*=\s*"(\/\/[^"]+)"/i',
            static fn (array $m): string => $m[1].'="'.$scheme.':'.$m[2].'"',
            $html,
        );

        // src="/..." -> src="<origin>/..."
        $html = preg_replace_callback(
            '/(src|href)\s*=\s*"\/([^"]*)"/i',
            static fn (array $m) => $m[1].'="'.$origin.'/'.$m[2].'"',
            $html,
        );

        return $html;
    }
}
