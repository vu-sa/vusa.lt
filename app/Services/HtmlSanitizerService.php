<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitizes user-authored HTML before it is persisted, so stored content can be
 * rendered with `v-html` / `{!! !!}` without allowing script injection.
 */
class HtmlSanitizerService
{
    private HtmlSanitizer $commentSanitizer;

    public function __construct()
    {
        $this->commentSanitizer = new HtmlSanitizer($this->commentConfig());
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
}
