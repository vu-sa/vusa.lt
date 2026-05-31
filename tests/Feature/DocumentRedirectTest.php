<?php

use App\Helpers\ShortUrlHelper;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Document short URL redirect', function () {
    it('appends web=1 to force browser rendering', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?web=1');
    });

    it('appends web=1 when anonymous_url already has a query string', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123?e=abc',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?e=abc&web=1');
    });

    it('does not duplicate web=1 when anonymous_url already contains it', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123?web=1',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?web=1');
    });

    it('does not append web=1 for downloads so file downloads directly', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?download=1");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?download=1');
    });

    it('appends download and web=1 query parameters correctly when anonymous_url already has a query string', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123?cid=abc',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?download=1");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?cid=abc&download=1');
    });

    it('returns 404 for non-existent document', function () {
        $code = ShortUrlHelper::encode(999999);

        $response = $this->get("/d/{$code}");

        $response->assertNotFound();
    });

    it('returns 404 when document has no anonymous_url', function () {
        $document = Document::factory()->create([
            'anonymous_url' => null,
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}");

        $response->assertNotFound();
    });
});
