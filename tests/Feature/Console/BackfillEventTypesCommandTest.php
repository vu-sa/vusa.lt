<?php

use App\Models\Calendar;
use App\Models\Category;
use App\Models\EventType;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');

    // Migrations create the seeded vocabulary via a data migration, not a model factory —
    // RefreshDatabase re-runs migrations, so the eight seeded types already exist.
});

test('types a meeting announcement as posedis regardless of its title', function (): void {
    $meeting = Meeting::factory()->create();
    $event = Calendar::factory()->create([
        'title' => ['lt' => 'Visiškai neaiškus pavadinimas', 'en' => 'Ambiguous title'],
        'meeting_id' => $meeting->id,
    ]);

    $this->artisan('taxonomy:backfill-events', ['--force' => true])->assertSuccessful();

    expect($event->fresh()->eventType->slug)->toBe('posedis');
});

test('routes a former freshmen-camps category event to stovykla', function (): void {
    // Seeded by CategoriesSeeder (TestSeeder), not a factory — a fresh alias would collide
    // with the unique index.
    $category = Category::query()->where('alias', 'freshmen-camps')->firstOrFail();
    $event = Calendar::factory()->create([
        'title' => ['lt' => 'Kažkoks renginys', 'en' => 'Some event'],
        'category_id' => $category->id,
    ]);

    $this->artisan('taxonomy:backfill-events', ['--force' => true])->assertSuccessful();

    expect($event->fresh()->eventType->slug)->toBe('stovykla');
});

test('rule 3 (konferencija) wins over rule 6 (atstovavimas) on an overlapping title', function (): void {
    $event = Calendar::factory()->create([
        'title' => ['lt' => 'Ataskaitinė-rinkiminė konferencija su senatu', 'en' => ''],
    ]);

    $this->artisan('taxonomy:backfill-events', ['--force' => true])->assertSuccessful();

    expect($event->fresh()->eventType->slug)->toBe('konferencija');
});

test('matches each ordered title rule', function (string $title, string $expectedSlug): void {
    $event = Calendar::factory()->create(['title' => ['lt' => $title, 'en' => '']]);

    $this->artisan('taxonomy:backfill-events', ['--force' => true])->assertSuccessful();

    expect($event->fresh()->eventType->slug)->toBe($expectedSlug);
})->with([
    ['Rinkimai į Senatą', 'rinkimai'],
    ['Mokymai naujiems nariams', 'mokymai'],
    ['Darbo grupės posėdis', 'atstovavimas'],
    ['Registracijos terminas', 'terminas'],
    ['Šventė bendruomenei', 'renginys'],
]);

test('leaves a genuinely ambiguous title untyped and reports it', function (): void {
    $event = Calendar::factory()->create(['title' => ['lt' => 'Fiesta Personæ 26', 'en' => '']]);

    $this->artisan('taxonomy:backfill-events', ['--force' => true])->assertSuccessful();

    expect($event->fresh()->event_type_id)->toBeNull();

    Storage::disk('local')->assertExists('taxonomy/unmatched-events.txt');
    expect(Storage::disk('local')->get('taxonomy/unmatched-events.txt'))->toContain('Fiesta Personæ 26');
});

test('dry run writes nothing', function (): void {
    $event = Calendar::factory()->create([
        'title' => ['lt' => 'Mokymai', 'en' => ''],
    ]);

    $this->artisan('taxonomy:backfill-events', ['--dry-run' => true])->assertSuccessful();

    expect($event->fresh()->event_type_id)->toBeNull();
});

test('an already-typed event is left untouched and re-running is idempotent', function (): void {
    $manualType = EventType::query()->where('slug', 'renginys')->firstOrFail();
    $event = Calendar::factory()->create([
        'title' => ['lt' => 'Mokymai vadovams', 'en' => ''],
        'event_type_id' => $manualType->id,
    ]);

    $this->artisan('taxonomy:backfill-events', ['--force' => true])->assertSuccessful();
    expect($event->fresh()->event_type_id)->toBe($manualType->id);

    // Second run: nothing left to type, so the manual assignment still holds.
    $this->artisan('taxonomy:backfill-events', ['--force' => true])->assertSuccessful();
    expect($event->fresh()->event_type_id)->toBe($manualType->id);
});
