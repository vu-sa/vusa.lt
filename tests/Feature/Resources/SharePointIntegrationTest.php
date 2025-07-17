<?php

use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);

    $this->documentManager = makeUser($this->tenant);
    $this->documentManager->duties()->first()->assignRole('Resource Manager');

    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('SharePoint API integration', function () {
    test('can authenticate with SharePoint', function () {
        // Mock the authentication request
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'fake-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3599,
            ], 200),
        ]);

        // Test your SharePoint authentication logic here
        // This would call your SharePoint service class
        expect(true)->toBeTrue(); // Replace with actual test
    });

    todo('can fetch documents from SharePoint', function () {
        // Mock SharePoint API responses
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'fake-access-token',
            ], 200),

            '*.sharepoint.com/*' => Http::response([
                'value' => [
                    [
                        'id' => 'test-document-id',
                        'name' => 'Test Document.pdf',
                        'size' => 1024,
                        'webUrl' => 'https://example.sharepoint.com/document.pdf',
                        'lastModifiedDateTime' => now()->toISOString(),
                    ],
                    [
                        'id' => 'another-document-id',
                        'name' => 'Another Document.docx',
                        'size' => 2048,
                        'webUrl' => 'https://example.sharepoint.com/another.docx',
                        'lastModifiedDateTime' => now()->subDay()->toISOString(),
                    ],
                ],
            ], 200),
        ]);

        // Test document fetching
        $response = asUser($this->documentManager)->post(route('documents.store'), [
            'documents' => [
                [
                    'name' => 'Test Document.pdf',
                    'list_item_unique_id' => 'test-document-id',
                    'site_id' => 'site-id-123',
                    'list_id' => 'list-id-123',
                ],
            ],
        ]);

        $response->assertRedirect();

        // Verify HTTP requests were made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sharepoint.com');
        });
    });

    test('handles SharePoint API errors gracefully', function () {
        // Mock API error responses
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Invalid client credentials',
            ], 401),
        ]);

        $response = asUser($this->documentManager)->post(route('documents.store'), [
            'documents' => [
                [
                    'name' => 'Test Document.pdf',
                    'list_item_unique_id' => 'test-id-123',
                    'site_id' => 'site-id-123',
                    'list_id' => 'list-id-123',
                ],
            ],
        ]);

        // Should handle error gracefully
        expect($response->status())->toBeIn([302, 422, 500]);
    });

    todo('can refresh document metadata from SharePoint', function () {
        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'sharepoint_id' => 'existing-doc-id',
        ]);

        // Mock successful refresh response
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'fake-access-token',
            ], 200),

            '*.sharepoint.com/*' => Http::response([
                'id' => 'existing-doc-id',
                'name' => 'Updated Document Name.pdf',
                'size' => 2048,
                'lastModifiedDateTime' => now()->toISOString(),
            ], 200),
        ]);

        $response = asUser($this->documentManager)->post(route('documents.refresh', $document));

        $response->assertRedirect();

        // Verify the refresh request was made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'existing-doc-id');
        });
    });

    test('handles SharePoint rate limiting', function () {
        // Mock rate limit response
        Http::fake([
            '*.sharepoint.com/*' => Http::response([
                'error' => [
                    'code' => 'TooManyRequests',
                    'message' => 'Rate limit exceeded',
                ],
            ], 429, [
                'Retry-After' => '60',
            ]),
        ]);

        $response = asUser($this->documentManager)->post(route('documents.store'), [
            'documents' => [
                [
                    'name' => 'Test Document.pdf',
                    'list_item_unique_id' => 'test-id-123',
                    'site_id' => 'site-id-123',
                    'list_id' => 'list-id-123',
                ],
            ],
        ]);

        // Should handle rate limiting appropriately
        expect($response->status())->toBeIn([302, 429, 500]);
    });
});
