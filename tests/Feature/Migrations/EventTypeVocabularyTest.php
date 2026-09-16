<?php

use App\Models\Calendar;
use App\Models\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

function runAdjustEventTypeVocabularyMigration(): void
{
    (require base_path('database/migrations/2026_09_15_100300_adjust_event_type_vocabulary.php'))->up();
}

test('seeds susirinkimas instead of a generic event type', function (): void {
    $slugs = EventType::query()->orderBy('sort_order')->pluck('slug')->all();

    expect($slugs)->toBe([
        'atstovavimas',
        'posedis',
        'susirinkimas',
        'mokymai',
        'konferencija',
        'rinkimai',
        'stovykla',
        'terminas',
    ]);
});

test('removes a generic type left by an earlier working migration', function (): void {
    $genericTypeId = DB::table('event_types')->insertGetId([
        'slug' => 'renginys',
        'name' => json_encode(['lt' => 'Renginys', 'en' => 'Social event']),
        'is_active' => true,
        'sort_order' => 9,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $eventId = Calendar::factory()->create(['event_type_id' => $genericTypeId])->getKey();

    runAdjustEventTypeVocabularyMigration();

    expect(DB::table('event_types')->where('id', $genericTypeId)->exists())->toBeFalse()
        ->and(Calendar::query()->findOrFail($eventId)->event_type_id)->toBeNull();
});

test('corrects susirinkimas events typed by an earlier working migration', function (): void {
    $posedisId = EventType::query()->where('slug', 'posedis')->value('id');
    $eventId = Calendar::factory()->create([
        'title' => ['lt' => 'Visuotinis susirinkimas', 'en' => 'General assembly'],
        'event_type_id' => $posedisId,
    ])->getKey();

    runAdjustEventTypeVocabularyMigration();

    expect(Calendar::query()->findOrFail($eventId)->eventType->slug)->toBe('susirinkimas');
});
