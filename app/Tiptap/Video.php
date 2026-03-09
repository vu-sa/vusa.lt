<?php

namespace App\Tiptap;

use Tiptap\Core\Node;

/**
 * Video node for TipTap PHP rendering.
 * Renders HTML5 video elements from the JSON content.
 */
class Video extends Node
{
    public static $name = 'video';

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [
                'class' => 'aspect-video h-auto w-full rounded-xl shadow-lg',
            ],
        ];
    }

    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'video',
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $src = $node->attrs->src ?? '';

        if (empty($src)) {
            // Return empty div - content will just not display
            return ['div', ['class' => 'video-error']];
        }

        $videoAttrs = array_merge($this->options['HTMLAttributes'], [
            'src' => $src,
            'controls' => 'controls', // Boolean attributes need string value in tiptap-php
        ]);

        // Add optional attributes if present
        if (isset($node->attrs->width)) {
            $videoAttrs['width'] = $node->attrs->width;
        }

        if (isset($node->attrs->height)) {
            $videoAttrs['height'] = $node->attrs->height;
        }

        if (isset($node->attrs->poster)) {
            $videoAttrs['poster'] = $node->attrs->poster;
        }

        return ['video', $videoAttrs];
    }
}
