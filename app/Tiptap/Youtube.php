<?php

namespace App\Tiptap;

use Tiptap\Core\Node;

/**
 * YouTube node for TipTap PHP rendering.
 * Renders embedded YouTube videos from the JSON content.
 */
class Youtube extends Node
{
    public static $name = 'youtube';

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [
                'class' => 'aspect-video h-auto w-full rounded-xl shadow-lg',
            ],
            'allowFullscreen' => true,
            'width' => 640,
            'height' => 480,
        ];
    }

    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'div[data-youtube-video]',
            ],
            [
                'tag' => 'iframe[src*="youtube.com"]',
            ],
            [
                'tag' => 'iframe[src*="youtube-nocookie.com"]',
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $src = $node->attrs->src ?? '';

        // Extract video ID and build embed URL
        $videoId = $this->extractVideoId($src);

        if (! $videoId) {
            // Return empty div - content will just not display
            return ['div', ['class' => 'youtube-error']];
        }

        $embedUrl = "https://www.youtube-nocookie.com/embed/{$videoId}";

        // Add start time if present
        if (isset($node->attrs->start) && $node->attrs->start > 0) {
            $embedUrl .= "?start={$node->attrs->start}";
        }

        $width = $node->attrs->width ?? $this->options['width'];
        $height = $node->attrs->height ?? $this->options['height'];

        $iframeAttrs = array_merge($this->options['HTMLAttributes'], [
            'src' => $embedUrl,
            'width' => $width,
            'height' => $height,
            'frameborder' => '0',
            'allow' => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
        ]);

        if ($this->options['allowFullscreen']) {
            $iframeAttrs['allowfullscreen'] = 'true';
        }

        return [
            'div',
            ['data-youtube-video' => '', 'class' => 'youtube-wrapper'],
            ['iframe', $iframeAttrs],
        ];
    }

    /**
     * Extract YouTube video ID from various URL formats
     */
    protected function extractVideoId(string $url): ?string
    {
        // Handle youtu.be short URLs
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Handle youtube.com URLs (watch, embed, v)
        if (preg_match('/youtube\.com\/(?:watch\?v=|embed\/|v\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Handle youtube-nocookie.com URLs
        if (preg_match('/youtube-nocookie\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
