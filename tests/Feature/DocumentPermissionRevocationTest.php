<?php

use App\Enums\DocumentStatus;
use App\Jobs\RevokeSharepointPermissionJob;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('observer dispatches revocation on delete', function (): void {
    test('dispatches job when document with permission ID is deleted', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
            'sharepoint_permission_id' => 'perm-123',
        ]);

        $document->delete();

        Queue::assertPushed(RevokeSharepointPermissionJob::class, fn ($job) => $job->sharepointPermissionId === 'perm-123'
            && $job->documentId === $document->id
            && $job->sharepointId === $document->sharepoint_id);
    });

    test('does not dispatch job when document has no permission ID', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
            'sharepoint_permission_id' => null,
        ]);

        $document->delete();

        Queue::assertNotPushed(RevokeSharepointPermissionJob::class);
    });

    test('does not dispatch job when document has no anonymous URL', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'anonymous_url' => null,
            'sharepoint_permission_id' => 'perm-123',
        ]);

        $document->delete();

        Queue::assertNotPushed(RevokeSharepointPermissionJob::class);
    });

    test('does not dispatch job in local environment', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
            'sharepoint_permission_id' => 'perm-123',
        ]);

        $originalEnv = app()['env'];
        app()['env'] = 'local';

        try {
            $document->delete();
        } finally {
            app()['env'] = $originalEnv;
        }

        Queue::assertNotPushed(RevokeSharepointPermissionJob::class);
    });
});

describe('observer dispatches revocation on deactivation', function (): void {
    test('dispatches job and clears URL when a published document is hidden', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Published,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
            'sharepoint_permission_id' => 'perm-456',
        ]);

        $document->status = DocumentStatus::Hidden;
        $document->save();

        Queue::assertPushed(RevokeSharepointPermissionJob::class, fn ($job) => $job->sharepointPermissionId === 'perm-456'
            && $job->documentId === $document->id);

        $document->refresh();
        expect($document->anonymous_url)->toBeNull()
            ->and($document->sharepoint_permission_id)->toBeNull();
    });

    test('does not dispatch job when a hidden document is published', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Hidden,
            'anonymous_url' => null,
            'sharepoint_permission_id' => null,
        ]);

        $document->status = DocumentStatus::Published;
        $document->save();

        Queue::assertNotPushed(RevokeSharepointPermissionJob::class);
    });

    test('does not dispatch job when the status is not changing', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Published,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
            'sharepoint_permission_id' => 'perm-789',
        ]);

        // Update a different field
        $document->title = 'Updated title';
        $document->save();

        Queue::assertNotPushed(RevokeSharepointPermissionJob::class);
    });

    test('does not dispatch job on deactivation when no permission ID', function (): void {
        Queue::fake();

        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Published,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
            'sharepoint_permission_id' => null,
        ]);

        $document->status = DocumentStatus::Hidden;
        $document->save();

        Queue::assertNotPushed(RevokeSharepointPermissionJob::class);

        // URL should still be cleared on deactivation
        $document->refresh();
        expect($document->anonymous_url)->toBeNull();
    });
});

describe('public API', function (): void {
    test('only returns active documents with anonymous URLs', function (): void {
        $uniquePrefix = 'REVOC_TEST_'.uniqid();

        // Active with URL - should be returned
        Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Published,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test1',
            'title' => $uniquePrefix.' Active with URL',
        ]);

        // Inactive with URL - should NOT be returned
        Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Hidden,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test2',
            'title' => $uniquePrefix.' Inactive with URL',
        ]);

        // Active without URL - should NOT be returned
        Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Published,
            'anonymous_url' => null,
            'title' => $uniquePrefix.' Active without URL',
        ]);

        $response = $this->getJson(route('api.v1.documents.index', ['search' => $uniquePrefix]));

        $response->assertSuccessful();

        $data = $response->json('data');

        expect(collect($data))->toHaveCount(1)
            ->and($data[0]['title'])->toContain('Active with URL');
    });

    test('search respects active and URL filters', function (): void {
        Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Published,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
            'title' => 'Searchable Document',
        ]);

        Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Hidden,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test2',
            'title' => 'Searchable Hidden',
        ]);

        $response = $this->getJson(route('api.v1.documents.index', ['search' => 'Searchable']));

        $response->assertSuccessful();

        $data = $response->json('data');
        expect(collect($data)->where('title', 'Searchable Document')->count())->toBe(1)
            ->and(collect($data)->where('title', 'Searchable Hidden')->count())->toBe(0);
    });
});
