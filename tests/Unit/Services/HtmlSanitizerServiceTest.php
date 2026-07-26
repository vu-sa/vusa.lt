<?php

use App\Services\HtmlSanitizerService;

beforeEach(function () {
    $this->sanitizer = new HtmlSanitizerService;
});

describe('rich content: legitimate editor output survives', function () {
    /**
     * The allowlist mirrors Tiptap's `full` preset. If any of these are dropped,
     * saving a problem or an agenda note silently deletes the author's content —
     * which is a worse outcome than the XSS we are guarding against.
     */
    test('keeps every node the full preset can produce', function (string $html, string $expected) {
        expect($this->sanitizer->sanitizeRichContent($html))->toContain($expected);
    })->with([
        'paragraph' => ['<p>Tekstas</p>', '<p>Tekstas</p>'],
        'heading h2' => ['<h2 id="tema">Tema</h2>', '<h2 id="tema">Tema</h2>'],
        'heading h3' => ['<h3 id="sub">Sub</h3>', '<h3 id="sub">Sub</h3>'],
        'bold' => ['<p><strong>bold</strong></p>', '<strong>bold</strong>'],
        'italic' => ['<p><em>italic</em></p>', '<em>italic</em>'],
        'strike' => ['<p><s>gone</s></p>', '<s>gone</s>'],
        'underline' => ['<p><u>under</u></p>', '<u>under</u>'],
        'subscript' => ['<p>H<sub>2</sub>O</p>', '<sub>2</sub>'],
        'superscript' => ['<p>x<sup>2</sup></p>', '<sup>2</sup>'],
        'inline code' => ['<p><code>$x</code></p>', '<code>$x</code>'],
        'blockquote' => ['<blockquote><p>cite</p></blockquote>', '<blockquote>'],
        'horizontal rule' => ['<p>a</p><hr><p>b</p>', '<hr'],
        'bullet list' => ['<ul><li>vienas</li></ul>', '<li>vienas</li>'],
        'ordered list' => ['<ol><li>pirmas</li></ol>', '<li>pirmas</li>'],
        'link' => ['<p><a href="https://vusa.lt">VU SR</a></p>', 'href="https://vusa.lt"'],
        'image' => ['<img src="/uploads/foto.png" alt="Foto" width="640">', 'src="/uploads/foto.png"'],
        'image alt text' => ['<img src="/uploads/foto.png" alt="Aprašymas">', 'alt="Aprašymas"'],
        // The `=` padding comes back HTML-encoded as `&#61;`, which the browser
        // decodes on parse — the image still resolves.
        'base64 image' => [
            '<img src="data:image/png;base64,iVBORw0KGgo=" alt="inline">',
            'src="data:image/png;base64,iVBORw0KGgo',
        ],
        'image alignment' => ['<img src="/a.png" data-align="left">', 'data-align="left"'],
        'video' => ['<video controls="true" src="/uploads/klipas.mp4"></video>', 'src="/uploads/klipas.mp4"'],
        'youtube embed' => [
            '<div data-youtube-video><iframe src="https://www.youtube.com/embed/abc123"></iframe></div>',
            'src="https://www.youtube.com/embed/abc123"',
        ],
        'table cell' => ['<table><tbody><tr><td colspan="2">langelis</td></tr></tbody></table>', '<td colspan="2">'],
        'table header' => ['<table><thead><tr><th>Antraštė</th></tr></thead></table>', '<th>Antraštė</th>'],
    ]);

    test('keeps a full document intact', function () {
        $html = '<h2 id="t">Problema</h2><p>Aprašymas su <strong>bold</strong> ir '
            .'<a href="https://vusa.lt">nuoroda</a>.</p>'
            .'<img src="/uploads/a.png" alt="A">'
            .'<table><tbody><tr><td>1</td></tr></tbody></table>';

        $clean = $this->sanitizer->sanitizeRichContent($html);

        expect($clean)
            ->toContain('<h2 id="t">Problema</h2>')
            ->toContain('<strong>bold</strong>')
            ->toContain('href="https://vusa.lt"')
            ->toContain('src="/uploads/a.png"')
            ->toContain('<td>1</td>');
    });
});

describe('rich content: hostile markup is removed', function () {
    test('strips dangerous constructs', function (string $html, string $forbidden) {
        expect($this->sanitizer->sanitizeRichContent($html))->not->toContain($forbidden);
    })->with([
        'script tag' => ['<p>hi</p><script>alert(1)</script>', '<script'],
        'img onerror' => ['<img src="x" onerror="alert(1)">', 'onerror'],
        'body onload' => ['<p onmouseover="alert(1)">hover</p>', 'onmouseover'],
        'javascript href' => ['<a href="javascript:alert(1)">click</a>', 'javascript:'],
        'inline style' => ['<p style="position:fixed;top:0">overlay</p>', 'style='],
        'iframe from another host' => [
            '<iframe src="https://evil.example/phish"></iframe>',
            'evil.example',
        ],
        'form' => ['<form action="https://evil.example"><input name="pw"></form>', '<form'],
        'object' => ['<object data="evil.swf"></object>', '<object'],
    ]);

    test('an anchor is forced to rel=noopener', function () {
        expect($this->sanitizer->sanitizeRichContent('<a href="https://x.lt">x</a>'))
            ->toContain('rel="noopener noreferrer nofollow"');
    });

    test('a data: URI cannot smuggle a script through an iframe', function () {
        $clean = $this->sanitizer->sanitizeRichContent(
            '<iframe src="data:text/html,<script>alert(1)</script>"></iframe>'
        );

        expect($clean)->not->toContain('alert(1)');
    });
});

describe('comment profile is unchanged', function () {
    test('still strips script from a comment body', function () {
        expect($this->sanitizer->sanitizeCommentBody('<p>hi</p><script>alert(1)</script>'))
            ->toBe('<p>hi</p>');
    });

    test('does not gain rich-content elements', function () {
        expect($this->sanitizer->sanitizeCommentBody('<h2>Big</h2><img src="/a.png">'))
            ->not->toContain('<h2>')
            ->not->toContain('<img');
    });
});
