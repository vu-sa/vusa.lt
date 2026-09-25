<?php

namespace App\Tiptap;

use Tiptap\Core\DOMSerializer;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Marks\Link;
use Tiptap\Marks\Subscript;
use Tiptap\Marks\Superscript;
use Tiptap\Marks\Underline;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

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
                new StarterKit([
                    'heading' => false, // Use CustomHeading instead
                    'codeBlock' => false,
                    'listItem' => false, // Use custom ListItem to fix doubled closing tags
                ]),

                // Custom ListItem to fix wrapper issue causing doubled tags
                new TipTapListItem,

                // Custom heading with ID support
                new CustomHeading([
                    'levels' => [2, 3, 4],
                ]),

                // Class-based text alignment (heading + paragraph) — not the package's
                // own TextAlign, which renders inline `style` and would be stripped by
                // HtmlSanitizerService (see App\Tiptap\TextAlign's docblock).
                new TextAlign,

                // The MembershipPage-style dot-pill "tag" mark.
                new RCTag,

                // Image carrying the author's size and alignment (App\Tiptap\Image);
                // the class itself is computed per alignment in its renderHTML().
                new Image([
                    'HTMLAttributes' => [
                        'loading' => 'lazy',
                    ],
                ]),

                // Table chrome comes from `.rc-table` in typography.css, not baked
                // utilities, so already-stored tables restyle along with new ones.
                new Table([
                    'HTMLAttributes' => [
                        'class' => 'rc-table',
                    ],
                ]),
                new TableCell,
                new TableHeader,
                new TableRow,

                // Media nodes
                new Youtube([
                    'HTMLAttributes' => [
                        'class' => 'rc-embed',
                    ],
                ]),
                new Video([
                    'HTMLAttributes' => [
                        'class' => 'rc-embed',
                    ],
                ]),

                // Text marks
                new Link([
                    'HTMLAttributes' => [
                        // `text-brand` resolves per theme (red light / amber dark) —
                        // the token `.rc-prose a` also uses. On the public surface
                        // `.rc-prose a` owns the colour anyway; this covers stored
                        // HTML rendered outside a prose wrapper.
                        'class' => 'text-brand underline font-medium tracking-normal',
                    ],
                ]),
                new Underline,
                new Subscript,
                new Superscript,
            ],
        ]);
    }

    /**
     * The PHP renderer (unlike the JS editor's NodeView) can't nest a wrapper tag around
     * a node that itself renders nested content — {@see DOMSerializer}'s
     * render-tree walker only unwraps one level of tag nesting. A resized table's explicit
     * column widths can add up past the reading measure, so tables get a scrollable wrapper
     * here instead, after rendering. Mirrors the wrapper `resources/js/Components/RichContent
     * /RichContentTiptapHTML.vue`'s client-side fallback renderer adds.
     */
    #[\Override]
    public function getHTML(): string
    {
        return preg_replace_callback(
            '/<table\b.*?<\/table>/s',
            fn (array $match): string => '<div class="tableWrapper">'.$match[0].'</div>',
            parent::getHTML(),
        );
    }
}
