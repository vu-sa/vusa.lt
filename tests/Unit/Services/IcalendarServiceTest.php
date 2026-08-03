<?php

use App\Models\Calendar;
use App\Services\IcalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Create published calendar events
    Calendar::factory()->count(3)->create([
        'is_draft' => false,
        'is_international' => false,
    ]);

    Calendar::factory()->count(2)->create([
        'is_draft' => false,
        'is_international' => true,
    ]);
});

describe('IcalendarService caching', function (): void {
    test('get returns cached result on second call', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;

        // Simulate request with lang parameter
        request()->merge(['lang' => 'lt']);

        $result1 = $service->get();
        $result2 = $service->get();

        expect($result1)->toBeString()->toEqual($result2)->toContain('BEGIN:VCALENDAR');
    });

    test('cache is keyed by language', function (): void {
        IcalendarService::clearCache();

        $service = new IcalendarService;

        // Get LT calendar
        request()->merge(['lang' => 'lt']);
        $ltResult = $service->get();

        // Get EN calendar (international only)
        request()->merge(['lang' => 'en']);
        $enResult = $service->get();

        // Both should be valid iCal
        expect($ltResult)->toContain('BEGIN:VCALENDAR');
        expect($enResult)->toContain('BEGIN:VCALENDAR');

        // LT result should have more events (all events) than EN (international only)
        $ltEventCount = substr_count($ltResult, 'BEGIN:VEVENT');
        $enEventCount = substr_count($enResult, 'BEGIN:VEVENT');

        expect($ltEventCount)->toBeGreaterThanOrEqual($enEventCount);
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

        expect($result)->toContain('Student activity calendar');
    });
});
