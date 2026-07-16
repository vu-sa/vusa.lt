<?php

namespace App\Services\HtmlSanitizer;

use Illuminate\Support\Uri;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Restricts `<iframe src>` to the video hosts the Tiptap editor can actually
 * produce.
 *
 * Symfony's own `allowMediaHosts()` applies to every media URL — `img`, `video`
 * and `iframe` alike — so using it to pin embeds to YouTube would also strip
 * every image not served from YouTube. This sanitizer narrows only the iframe,
 * leaving image hosts unrestricted.
 *
 * An iframe whose src is refused keeps the element but loses the attribute,
 * which renders as an empty frame rather than someone else's page.
 */
final class EmbedSourceSanitizer implements AttributeSanitizerInterface
{
    private const ALLOWED_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'youtu.be',
        'www.youtu.be',
    ];

    public function getSupportedElements(): array
    {
        return ['iframe'];
    }

    public function getSupportedAttributes(): array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $host = rescue(fn () => Uri::of($value)->host(), null, report: false);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return in_array(strtolower($host), self::ALLOWED_HOSTS, true) ? $value : null;
    }
}
