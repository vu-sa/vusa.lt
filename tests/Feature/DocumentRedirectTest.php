<?php

use App\Helpers\ShortUrlHelper;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Document short URL redirect', function () {
    it('redirects to anonymous_url when document exists', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        // The route may redirect through the language group first
        $response = $this->get("/d/{$code}");

        // Should be a redirect (302) to external URL
        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123');
    });

    it('returns 404 for non-existent document', function () {
        $code = ShortUrlHelper::encode(999999);

        $response = $this->get("/d/{$code}");

        $response->assertNotFound();
    });

    it('forwards query parameters to anonymous_url', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?download=1");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?download=1');
    });

    it('appends query parameters correctly when anonymous_url already has query string', function () {
        $document = Document::factory()->create([
            'anonymous_url' => 'https://sharepoint.example.com/document/123?cid=abc',
        ]);

        $code = ShortUrlHelper::encode($document->id);

        $response = $this->get("/d/{$code}?download=1");

        $response->assertStatus(302);
        expect($response->headers->get('Location'))->toBe('https://sharepoint.example.com/document/123?cid=abc&download=1');
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
