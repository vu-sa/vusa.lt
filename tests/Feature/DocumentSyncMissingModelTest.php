<?php

use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => null]);
    config(['scout.queue' => false]);
    config(['scout.after_commit' => false]);
});

test('sync job is silently deleted, not failed, when the document is gone by the time it runs', function (): void {
    // Needs a real queue round-trip (serialize/unserialize), unlike the sync
    // driver used elsewhere in this suite, to exercise deleteWhenMissingModels.
    config(['queue.default' => 'database']);

    $document = Document::factory()->create();

    SyncDocumentFromSharePointJob::dispatch($document);

    $document->delete();

    Artisan::call('queue:work', [
        'connection' => 'database',
        '--queue' => 'sharepoint-sync',
        '--once' => true,
    ]);

    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
});
