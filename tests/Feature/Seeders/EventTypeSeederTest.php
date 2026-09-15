<?php

use App\Models\EventType;
use Database\Seeders\EventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('restores the canonical event type vocabulary when run repeatedly', function (): void {
    EventType::query()->where('slug', 'susirinkimas')->firstOrFail()->delete();
    DB::table('event_types')->where('slug', 'posedis')->update([
        'name' => json_encode(['lt' => 'Neteisingas', 'en' => 'Incorrect']),
        'is_active' => false,
        'sort_order' => 99,
    ]);
    Cache::put('all-event-types-for-inertia', ['stale']);

    $this->seed(EventTypeSeeder::class);
    $this->seed(EventTypeSeeder::class);

    $eventTypes = DB::table('event_types')
        ->orderBy('sort_order')
        ->get(['slug', 'name', 'is_active', 'sort_order', 'deleted_at'])
        ->map(fn (object $eventType): array => [
            'slug' => $eventType->slug,
            'name' => json_decode($eventType->name, true),
            'is_active' => (bool) $eventType->is_active,
            'sort_order' => $eventType->sort_order,
            'deleted_at' => $eventType->deleted_at,
        ])
        ->all();

    expect($eventTypes)->toBe([
        ['slug' => 'atstovavimas', 'name' => ['lt' => 'Atstovavimas', 'en' => 'Representation'], 'is_active' => true, 'sort_order' => 1, 'deleted_at' => null],
        ['slug' => 'posedis', 'name' => ['lt' => 'Posėdis', 'en' => 'Meeting'], 'is_active' => true, 'sort_order' => 2, 'deleted_at' => null],
        ['slug' => 'susirinkimas', 'name' => ['lt' => 'Susirinkimas', 'en' => 'Assembly'], 'is_active' => true, 'sort_order' => 3, 'deleted_at' => null],
        ['slug' => 'mokymai', 'name' => ['lt' => 'Mokymai', 'en' => 'Training'], 'is_active' => true, 'sort_order' => 4, 'deleted_at' => null],
        ['slug' => 'konferencija', 'name' => ['lt' => 'Konferencija', 'en' => 'Conference'], 'is_active' => true, 'sort_order' => 5, 'deleted_at' => null],
        ['slug' => 'rinkimai', 'name' => ['lt' => 'Rinkimai', 'en' => 'Elections'], 'is_active' => true, 'sort_order' => 6, 'deleted_at' => null],
        ['slug' => 'stovykla', 'name' => ['lt' => 'Stovykla', 'en' => 'Camp'], 'is_active' => true, 'sort_order' => 7, 'deleted_at' => null],
        ['slug' => 'terminas', 'name' => ['lt' => 'Terminas', 'en' => 'Deadline'], 'is_active' => true, 'sort_order' => 8, 'deleted_at' => null],
    ]);
    expect(Cache::has('all-event-types-for-inertia'))->toBeFalse();
});
