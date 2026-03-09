<?php

namespace App\Tiptap;

use Tiptap\Nodes\ListItem;

/**
 * Custom ListItem that removes the wrapper to fix doubled closing tags
 * in the ueberdosis/tiptap-php library.
 */
class TipTapListItem extends ListItem
{
    public static function wrapper($DOMNode)
    {
        return null;
    }
}
