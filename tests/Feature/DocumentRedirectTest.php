<?php

use App\Helpers\ShortUrlHelper;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

describe('Document short URL redirect', function (): void {
    it('appends web=1 to force browser rendering', function (): void {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?web=1');
    });

    it('appends web=1 when anonymous_url already has a query string', function (): void {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123?e=abc',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?e=abc&web=1');
    });

    it('does not duplicate web=1 when anonymous_url already contains it', function (): void {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123?web=1',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?web=1');
    });

    it('does not append web=1 for downloads so file downloads directly', function (): void {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?download=1");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?download=1');
    });

    it('appends download and web=1 query parameters correctly when anonymous_url already has a query string', function (): void {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123?cid=abc',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?download=1");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?cid=abc&download=1');
    });

    it('returns 404 for non-existent document', function (): void {
        $code = ShortUrlHelper::encode(999999);

        $response = $this->get("/d/{$code}");

        $response->assertNotFound();
    });

    it('returns 404 when document has no anonymous_url', function (): void {
        $document = Document::factory()->create([
            'anonymous_url' => null,
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertNotFound();
    });
});

describe('Internet shortcut (.url) document redirect', function (): void {
    it('redirects straight to link_url, with no web=1 appended', function (): void {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/123',
            'link_url' => 'https://ataskaita2023.vusa.lt',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://ataskaita2023.vusa.lt');
    });

    it('ignores the download query parameter for a resolved shortcut', function (): void {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/123',
            'link_url' => 'https://ataskaita2023.vusa.lt',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?download=1");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://ataskaita2023.vusa.lt');
    });

    it('does not forward arbitrary query parameters for a resolved shortcut', function (): void {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/123',
            'link_url' => 'https://ataskaita2023.vusa.lt',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?foo=bar");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://ataskaita2023.vusa.lt');
    });

    it('falls back to anonymous_url when link_url is null', function (): void {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/123',
            'link_url' => null,
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/shortcut/123?web=1');
    });

    it('falls back to anonymous_url when link_url has a disallowed scheme', function (): void {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/123',
            'link_url' => 'javascript:alert(1)',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        expect($location)->toBe('https://sharepoint.example.com/shortcut/123?web=1')->not->toContain('javascript:');
    });
});
