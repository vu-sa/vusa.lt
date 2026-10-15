<?php

use App\Models\Calendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

/** The schema is already migrated, so bring the column back, seed it, then run up() again. */
function moveCalendarVideoUrlMigration(): object
{
    return require base_path('database/migrations/2026_09_25_212828_move_calendar_video_url_into_description.php');
}

function calendarWithVideo(array $description, ?string $videoUrl): int
{
    $id = Calendar::factory()->create(['description' => $description])->getKey();
    DB::table('calendar')->where('id', $id)->update(['video_url' => $videoUrl]);

    return $id;
}

beforeEach(function (): void {
    moveCalendarVideoUrlMigration()->down();
});

test('appends the video to every description language that has text', function (): void {
    $id = calendarWithVideo(['lt' => '<p>Aprašymas</p>', 'en' => '<p>Description</p>'], 'dQw4w9WgXcQ');

    moveCalendarVideoUrlMigration()->up();

    $event = Calendar::query()->findOrFail($id);
    foreach (['lt' => '<p>Aprašymas</p>', 'en' => '<p>Description</p>'] as $locale => $text) {
        expect($event->getTranslation('description', $locale))
            ->toStartWith($text)
            ->toContain('data-youtube-video')
            ->toContain('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
    }
});

test('puts the video in the Lithuanian description when there is none', function (): void {
    $id = calendarWithVideo(['lt' => '', 'en' => ''], 'dQw4w9WgXcQ');

    moveCalendarVideoUrlMigration()->up();

    expect(Calendar::query()->findOrFail($id)->getTranslation('description', 'lt'))
        ->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ');
});

test('accepts a pasted YouTube address as well as a bare ID', function (): void {
    $id = calendarWithVideo(['lt' => '<p>Tekstas</p>'], 'https://youtu.be/dQw4w9WgXcQ?t=5');

    moveCalendarVideoUrlMigration()->up();

    expect(Calendar::query()->findOrFail($id)->getTranslation('description', 'lt'))
        ->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ');
});

test('leaves events without a usable video untouched and drops the column', function (): void {
    $id = calendarWithVideo(['lt' => '<p>Tekstas</p>'], 'not a video');

    moveCalendarVideoUrlMigration()->up();

    expect(Calendar::query()->findOrFail($id)->getTranslation('description', 'lt'))->toBe('<p>Tekstas</p>')
        ->and(Schema::hasColumn('calendar', 'video_url'))->toBeFalse();
});

// The embed has to survive the next save, which runs the description through the sanitizer.
test('keeps the embed when the event is saved again', function (): void {
    $id = calendarWithVideo(['lt' => '<p>Tekstas</p>'], 'dQw4w9WgXcQ');
    moveCalendarVideoUrlMigration()->up();

    $event = Calendar::query()->findOrFail($id);
    $event->setTranslation('description', 'lt', $event->getTranslation('description', 'lt'))->save();

    expect($event->fresh()->getTranslation('description', 'lt'))
        ->toContain('data-youtube-video')
        ->toContain('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"');
});
