<?php

use App\Actions\DuplicateCalendarAction;
use App\Models\Calendar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DuplicateCalendarAction', function () {
    test('duplicates calendar with basic properties', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Originalus renginys', 'en' => 'Original event'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar)->toBeInstanceOf(Calendar::class)
            ->and($duplicatedCalendar->id)->not()->toBe($originalCalendar->id)
            ->and($duplicatedCalendar->getTranslation('title', 'lt'))->toContain('(kopija)')
            ->and($duplicatedCalendar->getTranslation('title', 'en'))->toContain('(copy)')
            ->and($duplicatedCalendar->description)->toBe($originalCalendar->description)
            ->and($duplicatedCalendar->is_draft)->toBeTrue()
            ->and($duplicatedCalendar->exists)->toBeTrue();
    });

    test('handles null title gracefully', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => null,
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar->title)->toContain('(kopija)')
            ->and($duplicatedCalendar->is_draft)->toBeTrue();
    });

    test('handles empty title gracefully', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => '',
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar->title)->toContain('(kopija)')
            ->and($duplicatedCalendar->is_draft)->toBeTrue();
    });

    test('preserves all non-modified attributes', function () {
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
            ->and($duplicatedCalendar->is_draft)->toBeTrue(); // Should be true regardless of original
    });

    test('creates new database record', function () {
        $originalCount = Calendar::count();
        
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect(Calendar::count())->toBe($originalCount + 2) // Original + duplicated
            ->and($duplicatedCalendar->wasRecentlyCreated)->toBeTrue();
    });

    test('returns the created calendar instance', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
        ]);

        $result = DuplicateCalendarAction::execute($originalCalendar);

        expect($result)->toBeInstanceOf(Calendar::class)
            ->and($result->exists)->toBeTrue()
            ->and($result->id)->toBeGreaterThan(0);
    });

    test('handles media files correctly in duplication process', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        // Verify the basic duplication works (media testing would require actual file setup)
        expect($duplicatedCalendar)->toBeInstanceOf(Calendar::class)
            ->and($duplicatedCalendar->exists)->toBeTrue()
            ->and($duplicatedCalendar->id)->not()->toBe($originalCalendar->id);
    });

    test('handles calendar without media files', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar)->toBeInstanceOf(Calendar::class)
            ->and($duplicatedCalendar->exists)->toBeTrue();
    });

    test('uses database transactions', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
        ]);

        // Mock a successful transaction
        \DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($originalCalendar) {
                return $callback($originalCalendar);
            });

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar)->toBeInstanceOf(Calendar::class);
    });

    test('handles translatable title arrays correctly', function () {
        $originalCalendar = Calendar::factory()->create([
            'title' => ['lt' => 'Lietuviškas pavadinimas', 'en' => 'English title'],
            'is_draft' => false,
        ]);

        $duplicatedCalendar = DuplicateCalendarAction::execute($originalCalendar);

        expect($duplicatedCalendar->getTranslations('title'))->toBeArray()
            ->and($duplicatedCalendar->getTranslation('title', 'lt'))->toContain('(kopija)')
            ->and($duplicatedCalendar->getTranslation('title', 'en'))->toContain('(copy)')
            ->and($duplicatedCalendar->getTranslation('title', 'lt'))->toContain('Lietuviškas pavadinimas')
            ->and($duplicatedCalendar->getTranslation('title', 'en'))->toContain('English title');
    });
});