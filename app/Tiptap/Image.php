<?php

namespace App\Tiptap;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

/**
 * Mirrors resources/js/Components/TipTap/AccessibleImage.ts.
 *
 * The package node drops `align` and pairs every image with a `w-full` class, so an
 * author's size and alignment choice existed only inside the editor and vanished from
 * the published page. Keep the rendered markup identical to the JS render extensions,
 * which the client-side fallback renderer still uses.
 */
class Image extends Node
{
    #[\Override]
    public static $name = 'image';

    private const array ALIGNMENT_CLASS = [
        'left' => 'float-left mr-4 mb-2',
        'right' => 'float-right ml-4 mb-2',
        'center' => 'mx-auto block',
    ];

    public function addOptions()
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function parseHTML()
    {
        return [
            ['tag' => 'img[src]'],
        ];
    }

    public function addAttributes()
    {
        return [
            'src' => [],
            'alt' => [],
            'title' => [],
            'width' => [],
            'height' => [],
            'align' => [
                'default' => 'center',
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('data-align') ?: 'center',
                'renderHTML' => fn ($attributes) => ['data-align' => $attributes->align ?? 'center'],
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = [])
    {
        $align = $node->attrs->align ?? 'center';

        return [
            'img',
            HTML::mergeAttributes(
                $this->options['HTMLAttributes'],
                $HTMLAttributes,
                ['class' => 'tiptap-image max-w-full h-auto rounded-md '.(self::ALIGNMENT_CLASS[$align] ?? self::ALIGNMENT_CLASS['center'])],
            ),
            0,
        ];
    }
}
