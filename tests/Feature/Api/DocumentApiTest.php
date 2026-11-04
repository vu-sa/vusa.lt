<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Document::factory()->count(3)->create([
        'institution_id' => Institution::factory()->for(Tenant::query()->first()),
        'status' => DocumentStatus::Published,
        'anonymous_url' => 'https://example.sharepoint.com/:b:/doc',
    ]);
});

describe('public document search', function (): void {
    test('caps the result count at the requested limit', function (): void {
        $this->getJson(route('api.v1.documents.index', ['limit' => 2]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    test('rejects a limit that would lift the cap', function (mixed $limit): void {
        $this->getJson(route('api.v1.documents.index', ['limit' => $limit]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    })->with([-1, 0, 51, 'all']);
});
