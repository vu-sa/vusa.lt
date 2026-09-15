<?php

use App\Models\Calendar;
use App\Models\EventType;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function runBackfillCalendarEventTypesMigration(): void
{
    (require base_path('database/migrations/2026_09_15_110000_backfill_calendar_event_types.php'))->up();
}

test('backfills an event type from its translated title', function (array $title, string $expectedSlug): void {
    $eventId = Calendar::factory()->create([
        'title' => $title,
        'event_type_id' => null,
    ])->getKey();

    runBackfillCalendarEventTypesMigration();

    expect(Calendar::query()->findOrFail($eventId)->eventType->slug)->toBe($expectedSlug);
})->with([
    'camp' => [['lt' => 'Pirmakursių stovykla', 'en' => 'Freshmen camp'], 'stovykla'],
    'conference before representation' => [['lt' => 'Konferencija su Senato nariais', 'en' => ''], 'konferencija'],
    'meeting title' => [['lt' => 'VU SA Revizijos komisijos posėdis', 'en' => ''], 'posedis'],
    'general meeting title' => [['lt' => 'Padalinio visuotinis susirinkimas', 'en' => ''], 'susirinkimas'],
    'elections' => [['lt' => 'Rinkimai į Senatą', 'en' => ''], 'rinkimai'],
    'training' => [['lt' => 'Mokymai naujiems nariams', 'en' => ''], 'mokymai'],
    'representation' => [['lt' => 'Susitikimas su rektoratu', 'en' => ''], 'atstovavimas'],
    'deadline' => [['lt' => 'Registracijos terminas', 'en' => ''], 'terminas'],
]);

test('backfills a linked meeting as posedis without an explicit susirinkimas title', function (): void {
    $meeting = Meeting::factory()->create();
    $eventId = Calendar::factory()->create([
        'title' => ['lt' => 'Konferencija', 'en' => 'Conference'],
        'meeting_id' => $meeting->id,
        'event_type_id' => null,
    ])->getKey();

    runBackfillCalendarEventTypesMigration();

    expect(Calendar::query()->findOrFail($eventId)->eventType->slug)->toBe('posedis');
});

test('an explicit susirinkimas title wins over a linked meeting', function (): void {
    $meeting = Meeting::factory()->create();
    $eventId = Calendar::factory()->create([
        'title' => ['lt' => 'Visuotinis susirinkimas', 'en' => 'General assembly'],
        'meeting_id' => $meeting->id,
        'event_type_id' => null,
    ])->getKey();

    runBackfillCalendarEventTypesMigration();

    expect(Calendar::query()->findOrFail($eventId)->eventType->slug)->toBe('susirinkimas');
});

test('does not overwrite an existing event type', function (): void {
    $eventType = EventType::query()->where('slug', 'terminas')->firstOrFail();
    $eventId = Calendar::factory()->create([
        'title' => ['lt' => 'Mokymai', 'en' => 'Training'],
        'event_type_id' => $eventType->id,
    ])->getKey();

    runBackfillCalendarEventTypesMigration();

    expect(Calendar::query()->findOrFail($eventId)->event_type_id)->toBe($eventType->id);
});

test('leaves a generic social event untyped', function (): void {
    $eventId = Calendar::factory()->create([
        'title' => ['lt' => 'Šventė bendruomenei', 'en' => 'Community celebration'],
        'event_type_id' => null,
    ])->getKey();

    runBackfillCalendarEventTypesMigration();

    expect(Calendar::query()->findOrFail($eventId)->event_type_id)->toBeNull();
});
