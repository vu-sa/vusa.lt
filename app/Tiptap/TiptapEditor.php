<?php

namespace App\Tiptap;

use Tiptap\Editor;

/**
 * TipTap Editor for server-side HTML rendering.
 *
 * Extensions are configured to match the frontend createRenderExtensions()
 * for consistent HTML output between PHP and JavaScript.
 */
class TiptapEditor extends Editor
{
    public function __construct()
    {
        parent::__construct([
            'extensions' => [
                // StarterKit provides basic nodes and marks
                new \Tiptap\Extensions\StarterKit([
                    'heading' => false, // Use CustomHeading instead
                    'codeBlock' => false,
                    'listItem' => false, // Use custom ListItem to fix doubled closing tags
                ]),

                // Custom ListItem to fix wrapper issue causing doubled tags
                new TipTapListItem,

                // Custom heading with ID support
                new CustomHeading([
                    'levels' => [2, 3],
                ]),

                // Image with responsive classes
                new \Tiptap\Nodes\Image([
                    'HTMLAttributes' => [
                        'class' => 'w-full rounded-md',
                        'loading' => 'lazy',
                    ],
                ]),

                // Table components with Tailwind classes
                new \Tiptap\Nodes\Table([
                    'HTMLAttributes' => [
                        'class' => 'border-collapse table-auto w-full tracking-normal',
                    ],
                ]),
                new \Tiptap\Nodes\TableCell([
                    'HTMLAttributes' => [
                        'class' => 'border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left tracking-normal [&[align=center]]:text-center [&[align=right]]:text-right',
                    ],
                ]),
                new \Tiptap\Nodes\TableHeader([
                    'HTMLAttributes' => [
                        'class' => 'border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left font-bold tracking-normal [&[align=center]]:text-center [&[align=right]]:text-right',
                    ],
                ]),
                new \Tiptap\Nodes\TableRow([
                    'HTMLAttributes' => [
                        'class' => 'm-0 border-t p-0 even:bg-zinc-100 dark:even:bg-zinc-800/20',
                    ],
                ]),

                // Media nodes
                new Youtube([
                    'HTMLAttributes' => [
                        'class' => 'aspect-video h-auto w-full rounded-xl shadow-lg',
                    ],
                ]),
                new Video([
                    'HTMLAttributes' => [
                        'class' => 'aspect-video h-auto w-full rounded-xl shadow-lg',
                    ],
                ]),

                // Text marks
                new \Tiptap\Marks\Link([
                    'HTMLAttributes' => [
                        'class' => 'text-vusa-red underline font-medium tracking-normal',
                    ],
                ]),
                new \Tiptap\Marks\Underline,
                new \Tiptap\Marks\Subscript,
                new \Tiptap\Marks\Superscript,
            ],
        ]);
    }
}
