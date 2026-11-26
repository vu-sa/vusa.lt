<?php

use App\Models\Calendar;
use App\Services\IcalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Create published calendar events
    Calendar::factory()->count(3)->create([
        'is_draft' => false,
        'is_international' => false,
        'title' => ['lt' => 'Vietinis renginys', 'en' => 'Local Only Event'],
    ]);

    Calendar::factory()->count(2)->create([
        'is_draft' => false,
        'is_international' => true,
        'title' => ['lt' => 'Tarptautinis renginys', 'en' => 'International Event'],
    ]);
});

describe('IcalendarService caching', function (): void {
    test('get returns cached result on second call without querying database', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;
        request()->merge(['lang' => 'lt']);

        $result1 = $service->get();
        expect(Cache::has('ical:calendar:lt'))->toBeTrue();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $result2 = $service->get();

        expect($queries)->toBe(0)
            ->and($result2)->toBe($result1)
            ->toContain('BEGIN:VCALENDAR');
    });

    test('cache is keyed by language with distinct event counts', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;

        request()->merge(['lang' => 'lt']);
        $ltResult = $service->get();

        request()->merge(['lang' => 'en']);
        $enResult = $service->get();

        expect(Cache::has('ical:calendar:lt'))->toBeTrue()
            ->and(Cache::has('ical:calendar:en'))->toBeTrue()
            ->and(substr_count($ltResult, 'BEGIN:VEVENT'))->toBe(5)
            ->and(substr_count($enResult, 'BEGIN:VEVENT'))->toBe(2);
    });

    test('clearCache clears all language caches', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;

        // Populate both caches
        request()->merge(['lang' => 'lt']);
        $service->get();

        request()->merge(['lang' => 'en']);
        $service->get();

        expect(Cache::has('ical:calendar:lt'))->toBeTrue()
            ->and(Cache::has('ical:calendar:en'))->toBeTrue();

        IcalendarService::clearCache();

        expect(Cache::has('ical:calendar:lt'))->toBeFalse()
            ->and(Cache::has('ical:calendar:en'))->toBeFalse();
    });

    test('cache is invalidated when calendar event is saved', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;

        request()->merge(['lang' => 'lt']);
        $service->get();

        expect(Cache::has('ical:calendar:lt'))->toBeTrue();

        // Save a new calendar event (triggers model's saved event)
        Calendar::factory()->create([
            'is_draft' => false,
            'is_international' => false,
        ]);

        // Cache should be cleared
        expect(Cache::has('ical:calendar:lt'))->toBeFalse();
    });

    test('cache is invalidated when calendar event is deleted', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;

        request()->merge(['lang' => 'lt']);
        $service->get();

        expect(Cache::has('ical:calendar:lt'))->toBeTrue();

        // Delete a calendar event
        Calendar::first()->delete();

        // Cache should be cleared
        expect(Cache::has('ical:calendar:lt'))->toBeFalse();
    });
});

describe('IcalendarService output', function (): void {
    test('generates valid iCal format', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;
        request()->merge(['lang' => 'lt']);

        $result = $service->get();

        expect($result)->toContain('BEGIN:VCALENDAR')
            ->toContain('END:VCALENDAR')
            ->toContain('BEGIN:VEVENT');
    });

    test('defaults to lt language when no lang specified', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;

        // Don't set lang parameter
        request()->replace([]);

        $result = $service->get();

        // Should return lt calendar (all events, not just international)
        expect($result)->toContain('BEGIN:VCALENDAR');
        expect($result)->toContain('Studentiškas kalendorius');
    });

    test('en language filters to international events only', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;
        request()->merge(['lang' => 'en']);

        $result = $service->get();

        expect($result)->toContain('Student activity calendar')
            ->toContain('International Event')
            ->not->toContain('Local Only Event')
            ->and(substr_count($result, 'BEGIN:VEVENT'))->toBe(2);
    });

    test('includes event location, all-day flag, and call-to-action url in ICS output', function (): void {
        Calendar::query()->delete();
        Calendar::factory()->create([
            'is_draft' => false,
            'is_international' => true,
            'is_all_day' => true,
            'date' => '2026-07-10 10:00:00',
            'end_date' => '2026-07-10 18:00:00',
            'title' => ['lt' => 'Dienos renginys', 'en' => 'Full Day Event'],
            'location' => ['lt' => 'Universiteto g. 3', 'en' => 'Universiteto St. 3'],
            'cto_url' => ['lt' => 'https://vusa.lt/lt/registracija', 'en' => 'https://vusa.lt/en/register'],
        ]);
        IcalendarService::clearCache();

        $service = new IcalendarService;
        request()->merge(['lang' => 'en']);

        $result = $service->get();

        expect($result)
            ->toContain('LOCATION:Universiteto St. 3')
            ->toContain('X-MICROSOFT-CDO-ALLDAYEVENT:TRUE')
            ->toContain('VALUE=DATE:20260710')
            ->toContain('URL:https://vusa.lt/en/register');
    });
});
