<?php

namespace App\Tiptap;

use Illuminate\Support\Str;
use Tiptap\Nodes\Heading;
use Tiptap\Utils\HTML;

class CustomHeading extends Heading
{
    public function parseHTML()
    {
        return array_map(function ($level) {
            return [
                'tag' => "h{$level}",
                'attrs' => [
                    'level' => $level,
                ],
                'getAttrs' => function ($DOMNode) {
                    return ['id' => $DOMNode->getAttribute('id')];
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

        // Merge the generated ID with any existing attributes
        $attributes = HTML::mergeAttributes(
            $this->options['HTMLAttributes'],
            $HTMLAttributes,
            ['id' => $id]
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
