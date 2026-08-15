<?php

use App\Models\News;
use App\Models\PublicNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    usesTypesense();

    config([
        'scout.prefix' => 'testing_public_sync_',
        'scout.queue' => false,
        'scout.soft_delete' => false,
    ]);
});

function publicSyncSearchHitIds(string $query): array
{
    return collect(PublicNews::search($query)->raw()['hits'] ?? [])
        ->pluck('document.id')
        ->map(fn ($id) => (string) $id)
        ->all();
}

test('flipping draft on removes a news article from the public index but keeps it in the admin one', function (): void {
    $query = 'Draft Flip News '.Str::uuid()->toString();

    $news = News::factory()->create([
        'title' => $query,
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);

    expect(publicSyncSearchHitIds($query))->toContain((string) $news->id);

    $news->update(['draft' => true]);

    expect(publicSyncSearchHitIds($query))->not->toContain((string) $news->id)
        // The admin index is untouched — a draft is still findable there.
        ->and($news->fresh()->shouldBeSearchable())->toBeTrue();
});

test('search:sync-public indexes a news article once its scheduled publish_time has passed', function (): void {
    $query = 'Scheduled News '.Str::uuid()->toString();

    $news = News::factory()->create([
        'title' => $query,
        'draft' => false,
        'publish_time' => now()->addMinutes(5),
    ]);

    // Not yet due: the model hooks already ran on create, and shouldBeSearchable() was false.
    expect(publicSyncSearchHitIds($query))->not->toContain((string) $news->id);

    $this->travelTo(now()->addMinutes(10));

    $this->artisan('search:sync-public')->assertExitCode(0);

    expect(publicSyncSearchHitIds($query))->toContain((string) $news->id);
});

test('search:sync-public removes a news article whose publish_time was pushed into the future by a mass update', function (): void {
    $query = 'Mass Updated News '.Str::uuid()->toString();

    $news = News::factory()->create([
        'title' => $query,
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);

    expect(publicSyncSearchHitIds($query))->toContain((string) $news->id);

    // A mass update fires no model events, so the News::saved() hook never runs —
    // this is exactly the gap search:sync-public exists to close.
    News::query()->where('id', $news->id)->update(['publish_time' => now()->addDay()]);

    $this->artisan('search:sync-public')->assertExitCode(0);

    expect(publicSyncSearchHitIds($query))->not->toContain((string) $news->id);
});
