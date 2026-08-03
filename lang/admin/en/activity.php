<?php

return [
    'title' => 'Activity log',
    'spotlight_description' => 'See who changed this record and when — including changes to related items.',
    'empty' => 'No changes yet.',
    'load_more' => 'Load more',
    'loading' => 'Loading...',
    'event' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'relation_updated' => 'Relations changed',
        'content_reordered' => 'Content blocks reordered',
    ],
    'rich_updated' => 'Content updated',
    'attached' => 'Added',
    'detached' => 'Removed',
    'system' => 'System',
    'filter' => [
        'all' => 'All',
        'scope_self' => 'This record only',
        'scope_tree' => 'Including related',
        'subject_type' => 'Type',
        'all_types' => 'All types',
    ],
    'boolean' => [
        'true' => 'Yes',
        'false' => 'No',
    ],
    'empty_value' => '—',
    // Label for a locale-expanded translatable field row, e.g. "Description (LT)".
    'field_locale' => ':field (:locale)',
    'diff' => [
        // Screen-reader-only prefixes announced before an inserted/removed
        // word run inside ActivityTextDiff.vue -- the visual strikethrough/
        // highlight alone doesn't convey meaning to assistive tech.
        'added' => 'Added:',
        'removed' => 'Removed:',
        'show_more' => 'Show unchanged text',
        'show_less' => 'Hide unchanged text',
    ],
    // Content-part label shown for block-level activities, e.g. "Text · #3".
    'block_position' => ':label · #:position',
    // ContentPartEnum::label() slugs. Only slugs with a non-obvious display
    // name strictly need an entry here -- ActivityChangeFormatter falls back
    // to Str::headline() for anything missing.
    'block' => [
        'image-grid' => 'Image grid',
        'shadcn-accordion' => 'Accordion',
        'shadcn-card' => 'Card',
        'tiptap' => 'Text',
        'hero' => 'Hero block',
        'spotify-embed' => 'Spotify embed',
        'social-embed' => 'Social media embed',
        'flow-graph' => 'Flow diagram',
        'number-stat-section' => 'Stats section',
        'news' => 'News block',
        'calendar' => 'Events block',
        'content-grid' => 'Content grid',
        'text-box' => 'Text box',
        'carousel-slide-deck' => 'Slide carousel',
        'card-stack' => 'Card stack',
        'photo-gallery' => 'Photo gallery',
        'link-list' => 'Link list',
        'event-list' => 'Event list',
        'person-quote' => 'Quote',
        'section' => 'Section',
        'spacer' => 'Spacer',
    ],
];
