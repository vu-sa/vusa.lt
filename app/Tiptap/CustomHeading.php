<?php

namespace App\Tiptap;

use Tiptap\Nodes\Heading as Heading;

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
}
