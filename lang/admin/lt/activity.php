<?php

return [
    'title' => 'Pakeitimų istorija',
    'spotlight_description' => 'Peržiūrėk, kas ir kada pakeitė šį įrašą — įskaitant susijusius pakeitimus.',
    'empty' => 'Pakeitimų nėra.',
    'load_more' => 'Rodyti daugiau',
    'loading' => 'Kraunama...',
    'event' => [
        'created' => 'Sukurta',
        'updated' => 'Atnaujinta',
        'deleted' => 'Ištrinta',
        'restored' => 'Atkurta',
        'relation_updated' => 'Ryšiai pakeisti',
        'content_reordered' => 'Turinio blokai pertvarkyti',
    ],
    'rich_updated' => 'Turinys atnaujintas',
    'attached' => 'Pridėta',
    'detached' => 'Pašalinta',
    'system' => 'Sistema',
    'filter' => [
        'all' => 'Visi',
        'scope_self' => 'Tik šis įrašas',
        'scope_tree' => 'Su susijusiais',
        'subject_type' => 'Tipas',
        'all_types' => 'Visi tipai',
    ],
    'boolean' => [
        'true' => 'Taip',
        'false' => 'Ne',
    ],
    'empty_value' => '—',
    // Label for a locale-expanded translatable field row, e.g. "Aprašymas (LT)".
    'field_locale' => ':field (:locale)',
    'diff' => [
        // Screen-reader-only prefixes announced before an inserted/removed
        // word run inside ActivityTextDiff.vue -- the visual strikethrough/
        // highlight alone doesn't convey meaning to assistive tech.
        'added' => 'Pridėta:',
        'removed' => 'Pašalinta:',
        'show_more' => 'Rodyti daugiau nepakitusio teksto',
        'show_less' => 'Slėpti nepakitusį tekstą',
    ],
    // Content-part label shown for block-level activities, e.g. "Tekstas · #3".
    'block_position' => ':label · #:position',
    // ContentPartEnum::label() slugs. Only slugs with a non-obvious display
    // name strictly need an entry here -- ActivityChangeFormatter falls back
    // to Str::headline() for anything missing.
    'block' => [
        'image-grid' => 'Nuotraukų tinklelis',
        'shadcn-accordion' => 'Akordeonas',
        'shadcn-card' => 'Kortelė',
        'tiptap' => 'Tekstas',
        'hero' => 'Pagrindinis blokas (Hero)',
        'spotify-embed' => 'Spotify grojaraštis',
        'social-embed' => 'Socialinių tinklų įrašas',
        'flow-graph' => 'Schema',
        'number-stat-section' => 'Statistikos blokas',
        'news' => 'Naujienų blokas',
        'calendar' => 'Renginių blokas',
        'content-grid' => 'Turinio tinklelis',
        'text-box' => 'Teksto laukas',
        'carousel-slide-deck' => 'Skaidrių karuselė',
        'hero-carousel' => 'Hero karuselė',
        'card-stack' => 'Kortelių rietuvė',
        'photo-gallery' => 'Nuotraukų galerija',
        'link-list' => 'Nuorodų sąrašas',
        'event-list' => 'Renginių sąrašas',
        'person-quote' => 'Citata',
        'section' => 'Skiltis',
        'spacer' => 'Tarpas',
    ],
];
