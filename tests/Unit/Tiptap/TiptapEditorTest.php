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
        expect($html)->toContain('<h2>Test H2</h2>');
        expect($html)->toContain('<h3>Test H3</h3>');
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
        expect($editor->setContent($h2Content)->getHTML())->toContain('<h2>');

        // Level 3 should work
        $h3Content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'H3']]],
            ],
        ];
        expect($editor->setContent($h3Content)->getHTML())->toContain('<h3>');
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
