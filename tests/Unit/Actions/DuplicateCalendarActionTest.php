<?php

use App\Actions\DuplicateCalendarAction;
use App\Actions\Media\AddImageMedia;
use App\Models\Calendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

describe('DuplicateCalendarAction', function (): void {
    test('duplicates calendar with basic properties and localized copy suffixes', function (): void {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Originalus renginys', 'en' => 'Original event'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar)->toBeInstanceOf(Calendar::class)
            ->and($duplicatedCalendar->id)->not()->toBe($originalCalendar->id)
            ->and($duplicatedCalendar->getTranslation('title', 'lt'))->toBe('Originalus renginys (kopija)')
            ->and($duplicatedCalendar->getTranslation('title', 'en'))->toBe('Original event (copy)')
            ->and($duplicatedCalendar->description)->toBe($originalCalendar->description)
            ->and($duplicatedCalendar->is_draft)->toBeTrue()
            ->and($duplicatedCalendar->exists)->toBeTrue();
    });

    test('handles null title gracefully', function (): void {
        $originalCalendar = Calendar::factory()->create([
            'title' => null,
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar->title)->toContain('(kopija)')
            ->and($duplicatedCalendar->is_draft)->toBeTrue();
    });

    test('handles empty title gracefully', function (): void {
        $originalCalendar = Calendar::factory()->create([
            'title' => '',
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar->title)->toContain('(kopija)')
            ->and($duplicatedCalendar->is_draft)->toBeTrue();
    });

    test('preserves all non-modified attributes', function (): void {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'date' => now()->addDays(5),
            'end_date' => now()->addDays(5)->addHours(2),
            'location' => ['lt' => 'Test lokacija', 'en' => 'Test location'],
            'organizer' => ['lt' => 'Test organizatorius', 'en' => 'Test organizer'],
            'cto_url' => ['lt' => 'https://example.lt', 'en' => 'https://example.com'],
            'is_international' => true,
            'is_all_day' => false,
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar->description)->toBe($originalCalendar->description)
            ->and($duplicatedCalendar->date->format('Y-m-d H:i'))->toBe($originalCalendar->date->format('Y-m-d H:i'))
            ->and($duplicatedCalendar->end_date->format('Y-m-d H:i'))->toBe($originalCalendar->end_date->format('Y-m-d H:i'))
            ->and($duplicatedCalendar->location)->toBe($originalCalendar->location)
            ->and($duplicatedCalendar->organizer)->toBe($originalCalendar->organizer)
            ->and($duplicatedCalendar->cto_url)->toBe($originalCalendar->cto_url)
            ->and($duplicatedCalendar->is_international)->toBe($originalCalendar->is_international)
            ->and($duplicatedCalendar->is_all_day)->toBe($originalCalendar->is_all_day)
            ->and($duplicatedCalendar->is_draft)->toBeTrue();
    });

    test('copies media files onto the duplicated calendar', function (): void {
        Storage::fake('spatieMediaLibrary');

        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
        ]);
        $originalMedia = app(AddImageMedia::class)->execute($originalCalendar, UploadedFile::fake()->image('event.png'), 'images');

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);
        $copiedMedia = $duplicatedCalendar->getFirstMedia('images');

        expect($copiedMedia)->not->toBeNull()
            ->and($copiedMedia->id)->not->toBe($originalMedia->id)
            ->and($copiedMedia->file_name)->toBe($originalMedia->file_name);
    });
});
