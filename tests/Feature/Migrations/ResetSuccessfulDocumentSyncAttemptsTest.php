<?php

use App\Jobs\SyncDocumentFromSharePointJob;
use App\Jobs\SyncStaleDocumentsJob;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

test('successful documents rejoin scheduled refresh without changing timestamps or failed attempts', function (): void {
    $this->freezeTime();
    Queue::fake();
    $successful = Document::factory()->create([
        'sync_status' => 'success',
        'sync_attempts' => 200,
        'checked_at' => now()->subDays(30),
        'last_sync_attempt_at' => now()->subDays(30),
        'updated_at' => now()->subDays(30),
    ]);
    $failed = Document::factory()->create([
        'sync_status' => 'failed',
        'sync_attempts' => 6,
        'checked_at' => now()->subDays(40),
    ]);
    $successfulBefore = (array) DB::table('documents')->where('id', $successful->id)->first();
    $failedBefore = (array) DB::table('documents')->where('id', $failed->id)->first();
    $migration = require database_path('migrations/2026_11_05_000000_reset_successful_document_sync_attempts.php');

    $migration->up();

    expect((array) DB::table('documents')->where('id', $successful->id)->first())
        ->toBe(array_replace($successfulBefore, ['sync_attempts' => 0]))
        ->and((array) DB::table('documents')->where('id', $failed->id)->first())->toBe($failedBefore);

    (new SyncStaleDocumentsJob)->handle();

    Queue::assertPushed(SyncDocumentFromSharePointJob::class, 1);
    Queue::assertPushed(SyncDocumentFromSharePointJob::class, fn ($job) => $job->document->is($successful));
});
