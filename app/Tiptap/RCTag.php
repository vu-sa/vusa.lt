<?php

namespace App\Tiptap;

use Tiptap\Core\Mark;
use Tiptap\Utils\HTML;

/**
 * PHP mirror of `resources/js/Components/TipTap/RCTag.ts` — the dot-pill "tag" from
 * MembershipPage.vue (see that file's docblock). Must produce the same
 * `rc-tag rc-tag-{variant} rc-tag-{color}` class output as the frontend's live
 * preview, since this mark is what actually renders on public pages
 * (ContentPart::getHtmlAttribute()).
 */
class RCTag extends Mark
{
    public static $name = 'rcTag';

    private const VARIANTS = ['filled', 'plain'];

    private const COLORS = ['zinc', 'red', 'yellow', 'green'];

    public function addOptions()
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function addAttributes()
    {
        return [
            'variant' => [
                'default' => 'filled',
                'renderHTML' => fn () => null,
            ],
            'color' => [
                'default' => 'yellow',
                'renderHTML' => fn () => null,
            ],
        ];
    }

    public function parseHTML()
    {
        return [
            [
                'tag' => 'span.rc-tag',
                'getAttrs' => function ($DOMNode) {
                    $classes = explode(' ', $DOMNode->getAttribute('class') ?? '');

                    $variant = collect(self::VARIANTS)->first(fn ($v) => in_array("rc-tag-{$v}", $classes, true)) ?? 'filled';
                    $color = collect(self::COLORS)->first(fn ($c) => in_array("rc-tag-{$c}", $classes, true)) ?? 'yellow';

                    return ['variant' => $variant, 'color' => $color];
                },
            ],
        ];
    }

    public function renderHTML($mark, $HTMLAttributes = [])
    {
        $variant = in_array($mark->attrs->variant ?? null, self::VARIANTS, true) ? $mark->attrs->variant : 'filled';
        $color = in_array($mark->attrs->color ?? null, self::COLORS, true) ? $mark->attrs->color : 'yellow';

        $classes = "rc-tag rc-tag-{$variant} rc-tag-{$color}";

        return [
            'span',
            HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes, ['class' => $classes]),
            0,
        ];
    }
}
