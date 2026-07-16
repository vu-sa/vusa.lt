<?php

namespace App\Services;

use App\Services\HtmlSanitizer\EmbedSourceSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitizes user-authored HTML before it is persisted, so stored content can be
 * rendered with `v-html` / `{!! !!}` without allowing script injection.
 */
class HtmlSanitizerService
{
    private HtmlSanitizer $commentSanitizer;

    private HtmlSanitizer $richContentSanitizer;

    public function __construct()
    {
        $this->commentSanitizer = new HtmlSanitizer($this->commentConfig());
        $this->richContentSanitizer = new HtmlSanitizer($this->richContentConfig());
    }

    /**
     * Sanitize a comment body produced by the Tiptap discussion editor.
     *
     * Keeps the editor's formatting marks, lists, links and @mention spans
     * (whose `data-id` drives notifications) while stripping everything else.
     */
    public function sanitizeCommentBody(string $html): string
    {
        return trim($this->commentSanitizer->sanitize($html));
    }

    /**
     * Sanitize HTML produced by the Tiptap editor's `full` preset — the one
     * behind `MultiLocaleTiptapFormItem`, used for problems and agenda notes.
     *
     * The allowlist deliberately mirrors `createFullExtensions()` in
     * `resources/js/Components/TipTap/extensions/presets.ts`: anything the
     * editor can legitimately produce must survive a round-trip, or saving a
     * record would silently delete the author's content.
     *
     * Known, accepted losses: `style` is stripped everywhere (it is the classic
     * CSS-injection vector), so the `<video>` node loses its inline
     * `width: 100%` and falls back to the stylesheet.
     */
    public function sanitizeRichContent(string $html): string
    {
        return trim($this->richContentSanitizer->sanitize($html));
    }

    private function commentConfig(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('s')
            ->allowElement('u')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('a', ['href', 'class'])
            ->allowElement('span', ['class'])
            ->allowAttribute('data-id', 'span')
            ->allowAttribute('data-type', 'span')
            ->allowAttribute('data-label', 'span')
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withMaxInputLength(500_000);
    }

    private function richContentConfig(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            // Blocks (StarterKit, minus the disabled codeBlock)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('blockquote')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('code')
            // CustomHeading (levels 2-3); `id` is generated for anchor links
            ->allowElement('h2', ['id', 'class'])
            ->allowElement('h3', ['id', 'class'])
            // Inline marks
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('s')
            ->allowElement('u')
            ->allowElement('sub')
            ->allowElement('sup')
            ->allowElement('span', ['class'])
            ->allowElement('a', ['href', 'class', 'target'])
            // AccessibleImage
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'class', 'loading'])
            ->allowAttribute('data-align', 'img')
            // Video
            ->allowElement('video', ['src', 'controls', 'width', 'height'])
            ->allowElement('source', ['src', 'type'])
            // Youtube renders an <iframe> inside a marker <div>
            ->allowElement('div', ['class'])
            ->allowAttribute('data-youtube-video', 'div')
            ->allowElement('iframe', ['src', 'width', 'height', 'title', 'allow', 'allowfullscreen', 'frameborder'])
            // TableKit
            ->allowElement('table', ['class'])
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tfoot')
            ->allowElement('tr', ['class'])
            ->allowElement('th', ['colspan', 'rowspan', 'colwidth', 'align', 'class'])
            ->allowElement('td', ['colspan', 'rowspan', 'colwidth', 'align', 'class'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            // `data:` is required — AccessibleImage is configured with
            // `allowBase64: true`, and a data: URI in an <img src> is not a
            // script-execution context.
            ->allowMediaSchemes(['https', 'http', 'data'])
            // Uploaded images are referenced by app-relative paths.
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withMaxInputLength(500_000);

        // Image hosts stay unrestricted (editors paste from anywhere), so embeds
        // are pinned to YouTube separately. See EmbedSourceSanitizer.
        return $config->withAttributeSanitizer(new EmbedSourceSanitizer);
    }
}
