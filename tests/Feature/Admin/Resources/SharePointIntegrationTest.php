<?php

use App\Models\Institution;
use App\Models\Tenant;
use Database\Seeders\RoleDocumentManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);

    $this->documentManager = makeUser($this->tenant);
    $this->documentManager->duties()->first()->assignRole(RoleDocumentManagerSeeder::NAME);

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

    todo('can refresh document metadata from SharePoint');
});
