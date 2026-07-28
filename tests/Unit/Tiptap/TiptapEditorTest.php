<?php

use App\Tiptap\TiptapEditor;

describe('TiptapEditor', function () {
    it('renders simple paragraph correctly', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello World']]],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toBe('<p>Hello World</p>');
    });

    it('renders headings without doubled closing tags', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Test H2']]],
                ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'Test H3']]],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        // Ensure no doubled closing tags like </h2></h2> or </h3></h3>
        expect($html)->not->toContain('</h2></h2>');
        expect($html)->not->toContain('</h3></h3>');
        expect($html)->toContain('id="test-h2"');
        expect($html)->toContain('id="test-h3"');
        expect($html)->toContain('>Test H2</h2>');
        expect($html)->toContain('>Test H3</h3>');
    });

    it('renders list items without doubled closing tags', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'bulletList',
                    'content' => [
                        ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Item 1']]]]],
                        ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Item 2']]]]],
                    ],
                ],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        // Ensure no doubled closing tags
        expect($html)->not->toContain('</li></li>');
        expect($html)->not->toContain('</p></p>');
        expect($html)->toContain('<li><p>Item 1</p></li>');
        expect($html)->toContain('<li><p>Item 2</p></li>');
    });

    it('renders images with correct attributes', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'image', 'attrs' => ['src' => '/images/test.jpg', 'alt' => 'Test image']],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('src="/images/test.jpg"');
        expect($html)->toContain('alt="Test image"');
        expect($html)->toContain('class="w-full rounded-md"');
        expect($html)->toContain('loading="lazy"');
    });

    it('renders tables with Tailwind classes', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'table',
                    'content' => [
                        [
                            'type' => 'tableRow',
                            'content' => [
                                ['type' => 'tableHeader', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Header']]]]],
                            ],
                        ],
                        [
                            'type' => 'tableRow',
                            'content' => [
                                ['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Cell']]]]],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<table');
        expect($html)->toContain('border-collapse');
        expect($html)->toContain('<th');
        expect($html)->toContain('<td');
    });

    it('renders links with styling', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com']]],
                            'text' => 'Click here',
                        ],
                    ],
                ],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('href="https://example.com"');
        expect($html)->toContain('text-vusa-red');
        expect($html)->toContain('>Click here</a>');
    });

    it('renders text marks correctly', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'bold'],
                        ['type' => 'text', 'marks' => [['type' => 'italic']], 'text' => 'italic'],
                        ['type' => 'text', 'marks' => [['type' => 'underline']], 'text' => 'underline'],
                        ['type' => 'text', 'marks' => [['type' => 'subscript']], 'text' => 'sub'],
                        ['type' => 'text', 'marks' => [['type' => 'superscript']], 'text' => 'sup'],
                    ],
                ],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<strong>bold</strong>');
        expect($html)->toContain('<em>italic</em>');
        expect($html)->toContain('<u>underline</u>');
        expect($html)->toContain('<sub>sub</sub>');
        expect($html)->toContain('<sup>sup</sup>');
    });

    it('handles empty content gracefully', function () {
        $content = [
            'type' => 'doc',
            'content' => [],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toBe('');
    });
});

describe('Youtube node', function () {
    it('renders YouTube embed with youtube-nocookie domain', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'youtube', 'attrs' => ['src' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ');
        expect($html)->toContain('allowfullscreen');
        expect($html)->toContain('class="aspect-video');
    });

    it('appends start time query param when present', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'youtube', 'attrs' => ['src' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'start' => 42]],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ?start=42');
    });

    it('omits start time query param when zero or missing', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'youtube', 'attrs' => ['src' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'start' => 0]],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ');
        expect($html)->not->toContain('start=');
    });

    it('extracts video ID from youtu.be short URL', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'youtube', 'attrs' => ['src' => 'https://youtu.be/abc123XYZ']],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('embed/abc123XYZ');
    });

    it('extracts video ID from embed URL', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'youtube', 'attrs' => ['src' => 'https://www.youtube.com/embed/testVideoId']],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('embed/testVideoId');
    });

    it('handles invalid YouTube URL gracefully', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'youtube', 'attrs' => ['src' => 'not-a-valid-url']],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<div class="youtube-error"></div>');
    });

    it('handles missing src attribute', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'youtube', 'attrs' => []],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<div class="youtube-error"></div>');
    });
});

describe('Video node', function () {
    it('renders HTML5 video with controls', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'video', 'attrs' => ['src' => '/videos/test.mp4']],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<video');
        expect($html)->toContain('src="/videos/test.mp4"');
        expect($html)->toContain('controls');
        expect($html)->toContain('class="aspect-video');
    });

    it('includes optional width and height attributes', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'video', 'attrs' => ['src' => '/videos/test.mp4', 'width' => 640, 'height' => 480]],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('width="640"');
        expect($html)->toContain('height="480"');
    });

    it('includes poster attribute when provided', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'video', 'attrs' => ['src' => '/videos/test.mp4', 'poster' => '/images/poster.jpg']],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('poster="/images/poster.jpg"');
    });

    it('handles missing src attribute', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'video', 'attrs' => []],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<div class="video-error"></div>');
    });
});

describe('CustomHeading', function () {
    it('only renders configured heading levels', function () {
        $editor = new TiptapEditor;

        // Level 2 should work
        $h2Content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'H2']]],
            ],
        ];
        $html = $editor->setContent($h2Content)->getHTML();
        expect($html)->toContain('<h2');
        expect($html)->toContain('id="h2"');

        // Level 3 should work
        $h3Content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'H3']]],
            ],
        ];
        $html = $editor->setContent($h3Content)->getHTML();
        expect($html)->toContain('<h3');
        expect($html)->toContain('id="h3"');
    });

    it('generates ID from heading text', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'My Test Heading']]],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('id="my-test-heading"');
        expect($html)->toContain('<h2 id="my-test-heading">My Test Heading</h2>');
    });

    it('generates ID from Lithuanian text', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Įvadas į programavimą']]],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('id="ivadas-i-programavima"');
    });

    it('handles special characters in heading text', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'What is this?! (Important)']]],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('id="what-is-this-important"');
    });

    it('handles headings with multiple text nodes', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 2],
                    'content' => [
                        ['type' => 'text', 'text' => 'Bold '],
                        ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'and'],
                        ['type' => 'text', 'text' => ' Italic'],
                    ],
                ],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('id="bold-and-italic"');
    });

    it('handles empty heading text', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => []],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        // Empty headings should render with empty string content
        expect($html)->toContain('<h2');
        expect($html)->toContain('</h2>');
    });

    it('renders h4 (levels 2-4)', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 4], 'content' => [['type' => 'text', 'text' => 'Sub point']]],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<h4 id="sub-point"');
    });

    it('renders size and accent as classes, never inline style', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2, 'size' => 'md', 'accent' => 'yellow'], 'content' => [['type' => 'text', 'text' => 'Title']]],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('rc-h-md');
        expect($html)->toContain('rc-h-accent-yellow');
        expect($html)->not->toContain('style=');
    });

    it('renders spacing as a class and leaves default unclassed', function () {
        $editor = new TiptapEditor;

        $tight = ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2, 'spacing' => 'tight'], 'content' => [['type' => 'text', 'text' => 'Title']]],
        ]];
        expect($editor->setContent($tight)->getHTML())
            ->toContain('rc-h-spacing-tight')->not->toContain('style=');

        $none = ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2, 'spacing' => 'none'], 'content' => [['type' => 'text', 'text' => 'Title']]],
        ]];
        expect($editor->setContent($none)->getHTML())->toContain('rc-h-spacing-none');

        $default = ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2, 'spacing' => 'default'], 'content' => [['type' => 'text', 'text' => 'Title']]],
        ]];
        expect($editor->setContent($default)->getHTML())->not->toContain('rc-h-spacing');
    });

    it('generates different IDs for both h2 and h3', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Section One']]],
                ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'Subsection One']]],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('id="section-one"');
        expect($html)->toContain('id="subsection-one"');
    });
});

describe('TextAlign', function () {
    it('renders center/end as a class, never inline style, and leaves start unclassed', function () {
        $editor = new TiptapEditor;

        $centered = ['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'attrs' => ['align' => 'center'], 'content' => [['type' => 'text', 'text' => 'Text']]],
        ]];
        $html = $editor->setContent($centered)->getHTML();
        expect($html)->toContain('rc-align-center');
        expect($html)->not->toContain('style=');

        $start = ['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'attrs' => ['align' => 'start'], 'content' => [['type' => 'text', 'text' => 'Text']]],
        ]];
        $html = $editor->setContent($start)->getHTML();
        expect($html)->not->toContain('rc-align');
    });

    it('applies to headings too', function () {
        $editor = new TiptapEditor;
        $content = ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2, 'align' => 'end'], 'content' => [['type' => 'text', 'text' => 'Title']]],
        ]];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('rc-align-end');
    });
});

describe('RCTag', function () {
    it('renders the dot-pill span with variant + color classes', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'marks' => [['type' => 'rcTag', 'attrs' => ['variant' => 'filled', 'color' => 'yellow']]], 'text' => 'Maskotė'],
                    ],
                ],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('<span class="rc-tag rc-tag-filled rc-tag-yellow">Maskotė</span>');
    });

    it('falls back to filled/yellow for an unrecognized variant or color', function () {
        $editor = new TiptapEditor;
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'marks' => [['type' => 'rcTag', 'attrs' => ['variant' => 'bogus', 'color' => 'bogus']]], 'text' => 'Text'],
                    ],
                ],
            ],
        ];

        $html = $editor->setContent($content)->getHTML();

        expect($html)->toContain('rc-tag-filled');
        expect($html)->toContain('rc-tag-yellow');
    });
});

describe('TipTapListItem', function () {
    it('renders without wrapper duplication', function () {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'orderedList',
                    'content' => [
                        ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'First']]]]],
                    ],
                ],
            ],
        ];

        $editor = new TiptapEditor;
        $html = $editor->setContent($content)->getHTML();

        // Count occurrences - should only have one closing tag per element
        expect(substr_count($html, '</li>'))->toBe(1);
        expect(substr_count($html, '</ol>'))->toBe(1);
    });
});
