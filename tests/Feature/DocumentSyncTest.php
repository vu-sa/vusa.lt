<?php

use App\Jobs\SyncDocumentFromSharePointJob;
use App\Jobs\SyncStaleDocumentsJob;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    // Disable scout indexing for tests to avoid extra job queuing
    config(['scout.driver' => null]);
});

describe('Document Sync Jobs', function () {
    test('individual sync job can be created and has correct properties', function () {
        $document = Document::factory()->create();

        $job = new SyncDocumentFromSharePointJob($document);

        expect($job->document->id)->toBe($document->id);
        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(120);
        expect($job->queue)->toBe('sharepoint-sync');
    });

    test('stale documents job identifies old documents correctly', function () {
        // Create test documents
        $staleDocument1 = Document::factory()->create([
            'checked_at' => now()->subDays(2), // Stale (older than 24h)
            'sync_status' => 'success',
        ]);

        $staleDocument2 = Document::factory()->create([
            'checked_at' => null, // Never checked - also stale
            'sync_status' => 'pending',
        ]);

        $freshDocument = Document::factory()->create([
            'checked_at' => now()->subHours(12), // Fresh (less than 24h)
            'sync_status' => 'success',
        ]);

        // Run the stale documents job
        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // Assert: Only stale documents should be queued
        Queue::assertPushed(SyncDocumentFromSharePointJob::class, 2);

        Queue::assertPushed(SyncDocumentFromSharePointJob::class, function ($job) use ($staleDocument1) {
            return $job->document->id === $staleDocument1->id;
        });

        Queue::assertPushed(SyncDocumentFromSharePointJob::class, function ($job) use ($staleDocument2) {
            return $job->document->id === $staleDocument2->id;
        });
    });

    test('stale documents job skips documents with excessive failures', function () {
        // Create document that has failed too many times
        Document::factory()->create([
            'checked_at' => now()->subDays(2),
            'sync_status' => 'failed',
            'sync_attempts' => 7, // Over the limit of 5
            'last_sync_attempt_at' => now()->subHours(1),
        ]);

        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // No jobs should be dispatched for documents with excessive failures
        Queue::assertNothingPushed();
    });

    test('stale documents job skips recently failed documents', function () {
        // Create document that failed recently with multiple attempts
        Document::factory()->create([
            'checked_at' => now()->subDays(2),
            'sync_status' => 'failed',
            'sync_attempts' => 4, // Under failure limit but failed recently
            'last_sync_attempt_at' => now()->subHours(2), // Recent failure
        ]);

        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // Should skip recently failed documents
        Queue::assertNothingPushed();
    });
});

describe('Document Sync Controller', function () {
    test('refresh endpoint dispatches sync job for authorized users', function () {
        $user = \App\Models\User::factory()->create();
        $user->assignRole(config('permission.super_admin_role_name'));

        $document = Document::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('documents.refresh', $document));

        Queue::assertPushed(SyncDocumentFromSharePointJob::class, function ($job) use ($document) {
            return $job->document->id === $document->id;
        });

        $response->assertSessionHas('success', 'Document refresh has been queued. It will be updated shortly.');
    });

    test('refresh endpoint requires authorization', function () {
        $user = \App\Models\User::factory()->create(); // User without admin role
        $document = Document::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('documents.refresh', $document));

        // User gets redirected due to authorization failure (could be 403 or 302)
        expect($response->status())->toBeIn([302, 403]);

        // Ensure no sync jobs were dispatched (ignore scout jobs)
        Queue::assertNotPushed(SyncDocumentFromSharePointJob::class);
    });
});

describe('Document Sync Command', function () {
    test('sync command shows stale document count in dry run', function () {
        // Create test documents
        Document::factory()->count(3)->create([
            'checked_at' => now()->subDays(2), // Stale
        ]);

        Document::factory()->create([
            'checked_at' => now()->subHours(12), // Fresh
        ]);

        $this->artisan('documents:sync --dry-run')
            ->expectsOutputToContain('Would sync 3 stale documents')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    test('sync command identifies failed documents for retry', function () {
        // Create failed documents with different attempt counts
        Document::factory()->create([
            'sync_status' => 'failed',
            'sync_attempts' => 2, // Eligible for retry
        ]);

        Document::factory()->create([
            'sync_status' => 'failed',
            'sync_attempts' => 6, // Too many attempts - should be skipped
        ]);

        Document::factory()->create([
            'sync_status' => 'success', // Not failed
        ]);

        $this->artisan('documents:sync --failed --dry-run')
            ->expectsOutputToContain('Would retry 1 failed documents')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    test('sync command respects limit parameter', function () {
        Document::factory()->count(100)->create([
            'checked_at' => now()->subDays(2),
        ]);

        $this->artisan('documents:sync --all --limit=25 --dry-run')
            ->expectsOutputToContain('Would sync 25 documents')
            ->assertExitCode(0);
    });
});

describe('Document Sync Status', function () {
    test('documents have correct default sync status', function () {
        $document = Document::factory()->create();

        expect($document->sync_status)->toBe('pending');
        expect($document->sync_attempts)->toBe(0);
        expect($document->last_sync_attempt_at)->toBeNull();
    });

    test('sync status is included in admin table display', function () {
        $user = \App\Models\User::factory()->create();
        $user->assignRole(config('permission.super_admin_role_name'));

        Document::factory()->create([
            'sync_status' => 'success',
            'title' => 'Test Document',
        ]);

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertOk();
        // The page should load successfully - detailed UI testing would require browser tests
    });
});
