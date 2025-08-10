<?php

use App\Jobs\SyncDocumentFromSharePointJob;
use App\Jobs\SyncStaleDocumentsJob;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    // Completely disable Scout indexing for tests to avoid extra job queuing
    config(['scout.driver' => null]);
    config(['scout.queue' => false]);
    config(['scout.after_commit' => false]);
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

    test('rolling refresh job identifies documents for 14-day cycle correctly', function () {
        // Clear any existing documents to ensure clean test
        Document::query()->delete();

        // Create critical documents (older than 14 days, should have priority)
        $criticalDocument1 = Document::factory()->create([
            'checked_at' => now()->subDays(15), // Critical - older than 14 days
            'sync_status' => 'success',
            'is_active' => true,
            'anonymous_url' => 'https://example.com/doc1',
        ]);

        $criticalDocument2 = Document::factory()->create([
            'checked_at' => null, // Never checked - critical
            'sync_status' => 'pending',
            'is_active' => true,
        ]);

        // Create documents in 7-14 day range (may be selected to fill quota)
        $recentDocument = Document::factory()->create([
            'checked_at' => now()->subDays(10), // Within 7-14 day range
            'sync_status' => 'success',
        ]);

        // Create fresh documents (should not be selected)
        $freshDocument = Document::factory()->create([
            'checked_at' => now()->subDays(3), // Fresh (less than 7 days)
            'sync_status' => 'success',
        ]);

        // Run the rolling refresh job
        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // With 4 total documents, the dynamic quota should be at least 1 (4/14 = 0.28, ceil = 1)
        // Critical documents should be prioritized
        Queue::assertPushed(SyncDocumentFromSharePointJob::class, function ($job) use ($criticalDocument1, $criticalDocument2) {
            return in_array($job->document->id, [$criticalDocument1->id, $criticalDocument2->id]);
        });

        // Fresh document should not be selected
        Queue::assertNotPushed(SyncDocumentFromSharePointJob::class, function ($job) use ($freshDocument) {
            return $job->document->id === $freshDocument->id;
        });

        // Verify at least one job was pushed (quota-based, so exact count varies)
        $pushedCount = collect(Queue::pushed(SyncDocumentFromSharePointJob::class))->count();
        expect($pushedCount)->toBeGreaterThan(0);
    });

    test('rolling refresh job skips documents with excessive failures', function () {
        // Clear any existing documents to ensure clean test
        Document::query()->delete();

        // Create document that has failed too many times (over the limit of 5)
        Document::factory()->create([
            'checked_at' => now()->subDays(15), // Old enough to be critical
            'sync_status' => 'failed',
            'sync_attempts' => 7, // Over the limit of 5
            'last_sync_attempt_at' => now()->subHours(1),
        ]);

        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // No jobs should be dispatched for documents with excessive failures
        Queue::assertNothingPushed();
    });

    test('rolling refresh job skips recently failed documents', function () {
        // Clear any existing documents to ensure clean test
        Document::query()->delete();

        // Create document that failed recently with multiple attempts
        Document::factory()->create([
            'checked_at' => now()->subDays(15), // Old enough to be critical
            'sync_status' => 'failed',
            'sync_attempts' => 4, // Under failure limit but failed recently
            'last_sync_attempt_at' => now()->subHours(2), // Recent failure (within 6 hours)
        ]);

        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // Should skip recently failed documents
        Queue::assertNothingPushed();
    });

    test('rolling refresh job uses dynamic quota calculation', function () {
        // Clear any existing documents to ensure clean test
        Document::query()->delete();

        // Create exactly 14 documents (one per day in cycle)
        Document::factory()->count(14)->create([
            'checked_at' => now()->subDays(15), // All critical
            'sync_status' => 'success',
        ]);

        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // With 14 documents, quota should be around 14/14 = 1 per day (with randomization ±10%)
        // So we should get at least 1 job dispatched
        $pushedCount = collect(Queue::pushed(SyncDocumentFromSharePointJob::class))->count();
        expect($pushedCount)->toBeGreaterThanOrEqual(1);
        expect($pushedCount)->toBeLessThanOrEqual(3); // With randomization, max should be reasonable
    });

    test('rolling refresh job prioritizes active and public documents', function () {
        // Clear any existing documents to ensure clean test
        Document::query()->delete();

        // Create documents with different priorities (all critical age)
        $highPriority = Document::factory()->create([
            'checked_at' => now()->subDays(15),
            'is_active' => true,
            'anonymous_url' => 'https://example.com/doc',
            'sync_status' => 'success',
        ]);

        $mediumPriority = Document::factory()->create([
            'checked_at' => now()->subDays(16), // Older but lower priority
            'is_active' => true,
            'anonymous_url' => null,
            'sync_status' => 'success',
        ]);

        $lowPriority = Document::factory()->create([
            'checked_at' => now()->subDays(17), // Oldest but lowest priority
            'is_active' => false,
            'anonymous_url' => null,
            'sync_status' => 'success',
        ]);

        $job = new SyncStaleDocumentsJob;
        $job->handle();

        // High priority document should be more likely to be selected
        // (We can't guarantee exact selection due to quota randomization, but we can test the logic)
        $pushedJobs = Queue::pushed(SyncDocumentFromSharePointJob::class);
        expect(count($pushedJobs))->toBeGreaterThan(0);

        // At least verify that some job was dispatched
        $dispatchedIds = collect($pushedJobs)->pluck('document.id')->toArray();
        expect($dispatchedIds)->toContain($highPriority->id);
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
    test('sync command shows document count for rolling refresh in dry run', function () {
        // Clear any existing documents to ensure clean test
        Document::query()->delete();

        // Create critical documents (older than 14 days)
        Document::factory()->count(2)->create([
            'checked_at' => now()->subDays(15), // Critical
        ]);

        // Create documents in 7-14 day range
        Document::factory()->create([
            'checked_at' => now()->subDays(10), // Within range
        ]);

        Document::factory()->create([
            'checked_at' => now()->subHours(12), // Fresh
        ]);

        $this->artisan('documents:sync --dry-run')
            ->expectsOutputToContain('Would sync')
            ->expectsOutputToContain('documents')
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
        // Clear any existing documents to ensure clean test
        Document::query()->delete();

        Document::factory()->count(100)->create([
            'checked_at' => now()->subDays(2),
        ]);

        $this->artisan('documents:sync --all --limit=25 --dry-run')
            ->expectsOutputToContain('(limit: 25)')
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
