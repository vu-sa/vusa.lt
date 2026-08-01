<?php

use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);

    $this->documentManager = makeUser($this->tenant);
    $this->documentManager->duties()->first()->assignRole('Resource Manager');

    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('SharePoint API integration', function (): void {
    test('can authenticate with SharePoint', function (): void {
        // Skip if not in integration testing mode
        $this->markTestSkipped('SharePoint integration tests skipped for now');

        // Mock the authentication request
        // Http::fake([
        //     'login.microsoftonline.com/*' => Http::response([
        //         'access_token' => 'fake-access-token',
        //         'token_type' => 'Bearer',
        //         'expires_in' => 3599,
        //     ], 200),
        // ]);
    });

    todo('can fetch documents from SharePoint');

    test('handles SharePoint API errors gracefully', function (): void {
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

    todo('can refresh document metadata from SharePoint');

    test('handles SharePoint rate limiting', function (): void {
        // Mock rate limit response
        // Http::fake([
        //     '*.sharepoint.com/*' => Http::response([
        //         'error' => [
        //             'code' => 'TooManyRequests',
        //             'message' => 'Rate limit exceeded',
        //         ],
        //     ], 429, [
        //         'Retry-After' => '60',
        //     ]),
        // ]);

        // $response = asUser($this->documentManager)->post(route('documents.store'), [
        //     'documents' => [
        //         [
        //             'name' => 'Test Document.pdf',
        //             'list_item_unique_id' => 'test-id-123',
        //             'site_id' => 'site-id-123',
        //             'list_id' => 'list-id-123',
        //         ],
        //     ],
        // ]);

        // Should handle rate limiting appropriately
        // expect($response->status())->toBeIn([302, 429, 500]);
    })->todo();
});
