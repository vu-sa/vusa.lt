<?php

namespace App\Tiptap;

use Tiptap\Editor as Editor;

class TiptapEditor extends Editor
{
    public function __construct()
    {
        $this->configuration = [
            'extensions' => [
                new \Tiptap\Extensions\StarterKit([
                    'heading' => false,
                    'codeBlock' => false,
                ]),
                new CustomHeading([
                    'levels' => [2, 3, 4],
                ]),
                new \Tiptap\Nodes\Image(),
                new \Tiptap\Nodes\Table([
                    'HTMLAttributes' => [
                        'class' => 'border-collapse table-auto w-full',
                    ],
                ]),
                new \Tiptap\Nodes\TableCell([
                    'HTMLAttributes' => [
                        'class' => 'border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left [&[align=center]]:text-center [&[align=right]]:text-right',
                    ],
                ]),
                new \Tiptap\Nodes\TableHeader([
                    'HTMLAttributes' => [
                        'class' => 'border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left font-bold [&[align=center]]:text-center [&[align=right]]:text-right',
                    ],
                ]),
                new \Tiptap\Nodes\TableRow([
                    'HTMLAttributes' => [
                        'class' => 'm-0 border-t p-0 even:bg-zinc-100 dark:even:bg-zinc-800/20',
                    ],
                ]),
                new \Tiptap\Marks\Link(),
                new \Tiptap\Marks\Underline(),
            ],
        ];

        parent::__construct();
    }
}
