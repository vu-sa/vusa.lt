<?php

use App\Models\ContentPart;

/**
 * getSearchableContent() used to silently return an empty string for every type
 * introduced after the initial rich-content set (card-stack, carousel-slide-deck,
 * photo-gallery, content-grid), and the `hero` case still read the pre-redesign
 * shape (`subtitle`/`buttonText`) after the frontend moved to `description`/`buttons[]`.
 */
function makePart(string $type, array $jsonContent, ?array $options = null): ContentPart
{
    return new ContentPart([
        'type' => $type,
        'json_content' => $jsonContent,
        'options' => $options,
    ]);
}

test('hero extracts title, description and button text (current shape)', function (): void {
    $part = makePart('hero', [
        'title' => 'Prisijunk prie VU SA',
        'description' => 'Atrask naujas galimybes',
        'buttons' => [
            ['text' => 'Tapk nariu', 'link' => '#'],
            ['text' => 'Sužinok daugiau', 'link' => '#'],
        ],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Prisijunk prie VU SA')
        ->toContain('Atrask naujas galimybes')
        ->toContain('Tapk nariu')
        ->toContain('Sužinok daugiau');
});

test('hero tolerates missing buttons/description', function (): void {
    $part = makePart('hero', ['title' => 'Only a title']);

    expect($part->getSearchableContent())->toBe('Only a title ');
});

test('carousel-slide-deck extracts title, badge and tiptap description', function (): void {
    $part = makePart('carousel-slide-deck', [
        [
            'title' => 'Bendruomenė',
            'badge' => 'Community',
            'description' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Rask bendraminčių']]],
                ],
            ],
        ],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Bendruomenė')
        ->toContain('Community')
        ->toContain('Rask bendraminčių');
});

test('hero-carousel extracts eyebrow, title, subtitle, tiptap description and button text', function (): void {
    $part = makePart('hero-carousel', [
        [
            'eyebrow' => 'VU SA kviečia',
            'title' => 'Prisijunk prie bendruomenės',
            'subtitle' => 'Atrask naujas galimybes',
            'description' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Rask bendraminčių']]],
                ],
            ],
            'buttons' => [
                ['text' => 'Tapk nariu', 'link' => '#'],
            ],
        ],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('VU SA kviečia')
        ->toContain('Prisijunk prie bendruomenės')
        ->toContain('Atrask naujas galimybes')
        ->toContain('Rask bendraminčių')
        ->toContain('Tapk nariu');
});

test('card-stack extracts card titles and descriptions', function (): void {
    $part = makePart('card-stack', [
        ['icon' => 'users', 'title' => 'Studijos', 'description' => 'Kokybiškos studijos'],
        ['icon' => 'star', 'title' => 'Bendruomenė', 'description' => 'Stipri organizacija'],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Studijos')
        ->toContain('Kokybiškos studijos')
        ->toContain('Bendruomenė')
        ->toContain('Stipri organizacija');
});

test('photo-gallery extracts image alt text', function (): void {
    $part = makePart('photo-gallery', [
        ['src' => '/a.webp', 'alt' => 'Studentai renginyje'],
        ['src' => '/b.webp', 'alt' => 'Diplomų įteikimas'],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Studentai renginyje')
        ->toContain('Diplomų įteikimas');
});

test('content-grid extracts tiptap and image column content', function (): void {
    $part = makePart('content-grid', [
        [
            'columns' => [
                [
                    'width' => 'col-span-6',
                    'content' => [
                        'type' => 'tiptap',
                        'value' => [
                            'type' => 'doc',
                            'content' => [
                                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Kairė skiltis']]],
                            ],
                        ],
                    ],
                ],
                [
                    'width' => 'col-span-6',
                    'content' => [
                        'type' => 'image',
                        'value' => '/image.webp',
                        'alt' => 'Dešinės skilties nuotrauka',
                    ],
                ],
            ],
        ],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Kairė skiltis')
        ->toContain('Dešinės skilties nuotrauka');
});

test('content-grid extracts card cell title and description', function (): void {
    $part = makePart('content-grid', [
        [
            'columns' => [
                [
                    'width' => 'col-span-7',
                    'content' => [
                        'type' => 'card',
                        'value' => ['title' => 'Kortelės pavadinimas', 'description' => 'Kortelės aprašymas'],
                    ],
                ],
            ],
        ],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Kortelės pavadinimas')
        ->toContain('Kortelės aprašymas');
});

test('link-list extracts section title and manual link titles, not resolved records', function (): void {
    $part = makePart('link-list', [
        'links' => [
            ['title' => 'Metinis pranešimas', 'url' => 'https://vusa.lt/a'],
        ],
    ], ['title' => 'Naudingos nuorodos', 'source' => 'news']);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Naudingos nuorodos')
        ->toContain('Metinis pranešimas');
});

test('event-list extracts only author-written option text', function (): void {
    $part = makePart('event-list', [], [
        'title' => 'Pirmakursių stovyklos',
        'emptyMessage' => 'Stovyklų dar nėra',
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Pirmakursių stovyklos')
        ->toContain('Stovyklų dar nėra');
});

test('person-quote extracts quote text, snapshot name and attribution', function (): void {
    $part = makePart('person-quote', [
        'quote' => [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Narystė man daug davė']]],
            ],
        ],
        'snapshot' => ['name' => 'Vardenė Pavardenė', 'attribution' => 'Koordinatorė, VU SA MIF'],
    ]);

    $content = $part->getSearchableContent();

    expect($content)
        ->toContain('Narystė man daug davė')
        ->toContain('Vardenė Pavardenė')
        ->toContain('Koordinatorė, VU SA MIF');
});

test('section extracts only its own title/subtitle, not the blocks it wraps', function (): void {
    // `section` has no `json_content` of its own — it's a marker RichContentParser
    // groups following parts under (see RichContentParser.vue's groupedContent); the
    // wrapped blocks are indexed separately, as their own ContentPart rows.
    $part = makePart('section', [], ['title' => 'VU SA skaičiais', 'subtitle' => 'Sužinok daugiau apie mus']);

    expect($part->getSearchableContent())
        ->toContain('VU SA skaičiais')
        ->toContain('Sužinok daugiau apie mus');
});

test('unhandled type returns an empty string rather than throwing', function (): void {
    $part = makePart('spotify-embed', ['url' => 'https://open.spotify.com/track/123']);

    expect($part->getSearchableContent())->toBeEmpty();
});
