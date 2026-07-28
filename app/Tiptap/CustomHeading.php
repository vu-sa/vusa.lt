<?php

namespace App\Tiptap;

use Illuminate\Support\Str;
use Tiptap\Nodes\Heading;
use Tiptap\Utils\HTML;

class CustomHeading extends Heading
{
    private const SIZE_CLASS = [
        'sm' => 'rc-h-sm',
        'md' => 'rc-h-md',
        'lg' => 'rc-h-lg',
        'xl' => 'rc-h-xl',
    ];

    private const ACCENT_CLASS = [
        'red' => 'rc-h-accent-red',
        'yellow' => 'rc-h-accent-yellow',
        'zinc' => 'rc-h-accent-zinc',
    ];

    // `default` has no class of its own — it falls through to the level-based
    // margin-block-start rules in app.css. Only the non-default densities render a class.
    private const SPACING_CLASS = [
        'tight' => 'rc-h-spacing-tight',
        'loose' => 'rc-h-spacing-loose',
        'none' => 'rc-h-spacing-none',
    ];

    public function parseHTML()
    {
        return array_map(function ($level) {
            return [
                'tag' => "h{$level}",
                'attrs' => [
                    'level' => $level,
                ],
                'getAttrs' => function ($DOMNode) {
                    $classes = explode(' ', $DOMNode->getAttribute('class') ?? '');
                    $size = collect(self::SIZE_CLASS)->search(fn ($class) => in_array($class, $classes, true)) ?: null;
                    $accent = collect(self::ACCENT_CLASS)->search(fn ($class) => in_array($class, $classes, true)) ?: null;
                    $spacing = collect(self::SPACING_CLASS)->search(fn ($class) => in_array($class, $classes, true)) ?: null;

                    return [
                        'id' => $DOMNode->getAttribute('id'),
                        'size' => $size,
                        'accent' => $accent,
                        'spacing' => $spacing,
                    ];
                },
            ];
        }, $this->options['levels']);
    }

    public function renderHTML($node, $HTMLAttributes = [])
    {
        $hasLevel = in_array($node->attrs->level, $this->options['levels']);

        $level = $hasLevel ?
            $node->attrs->level :
            $this->options['levels'][0];

        // Extract text content from the node to generate ID
        $text = $this->extractTextFromNode($node);
        $id = Str::slug($text);

        $size = $node->attrs->size ?? null;
        $accent = $node->attrs->accent ?? null;
        $spacing = $node->attrs->spacing ?? null;
        $classes = trim(
            ($size && isset(self::SIZE_CLASS[$size]) ? self::SIZE_CLASS[$size].' ' : '')
            .($accent && isset(self::ACCENT_CLASS[$accent]) ? self::ACCENT_CLASS[$accent].' ' : '')
            .($spacing && isset(self::SPACING_CLASS[$spacing]) ? self::SPACING_CLASS[$spacing] : '')
        );

        // Merge the generated ID with any existing attributes (including `class` from
        // the `align` global attribute — HTML::mergeAttributes concatenates `class`
        // values rather than overwriting, same as the JS side's mergeAttributes).
        $attributes = HTML::mergeAttributes(
            $this->options['HTMLAttributes'],
            $HTMLAttributes,
            array_filter(['id' => $id, 'class' => $classes ?: null])
        );

        return [
            "h{$level}",
            $attributes,
            0,
        ];
    }

    /**
     * Extract plain text content from a node recursively
     */
    private function extractTextFromNode($node): string
    {
        $text = '';

        if (isset($node->content) && is_array($node->content)) {
            foreach ($node->content as $childNode) {
                $text .= $this->extractTextFromNode($childNode);
            }
        } elseif (isset($node->text)) {
            $text .= $node->text;
        }

        return $text;
    }
}
