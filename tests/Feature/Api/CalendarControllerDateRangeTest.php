<?php

use App\Models\Calendar;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
});

describe('calendar API date range filtering', function () {
    test('returns events within default date range when no dates specified', function () {
        // Create events at various dates
        $pastEvent = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->subDays(3),
            'is_draft' => false,
        ]);

        $futureEvent = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->addDays(10),
            'is_draft' => false,
        ]);

        $outOfRangeEvent = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->subDays(30),
            'is_draft' => false,
        ]);

        $response = $this->getJson(route('api.v1.tenants.calendar.index', [
            'tenant' => $this->tenant->alias,
        ]));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => ['date_from', 'date_to', 'max_range_days'],
        ]);

        $data = $response->json('data');
        $eventIds = collect($data)->pluck('id')->toArray();

        // Events within default range (-7 to +21 days) should be included
        expect($eventIds)->toContain($pastEvent->id);
        expect($eventIds)->toContain($futureEvent->id);

        // Event outside range should not be included
        expect($eventIds)->not->toContain($outOfRangeEvent->id);
    });

    test('filters events by custom date_from and date_to parameters', function () {
        $eventInRange = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::parse('2026-02-15'),
            'is_draft' => false,
        ]);

        $eventOutOfRange = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::parse('2026-03-15'),
            'is_draft' => false,
        ]);

        $response = $this->getJson(route('api.v1.tenants.calendar.index', [
            'tenant' => $this->tenant->alias,
            'date_from' => '2026-02-01',
            'date_to' => '2026-02-28',
        ]));

        $response->assertSuccessful();

        $data = $response->json('data');
        $eventIds = collect($data)->pluck('id')->toArray();

        expect($eventIds)->toContain($eventInRange->id);
        expect($eventIds)->not->toContain($eventOutOfRange->id);

        // Verify meta shows the requested range
        expect($response->json('meta.date_from'))->toBe('2026-02-01');
        expect($response->json('meta.date_to'))->toBe('2026-02-28');
    });

    test('enforces maximum 455 day range limit', function () {
        $response = $this->getJson(route('api.v1.tenants.calendar.index', [
            'tenant' => $this->tenant->alias,
            'date_from' => '2024-01-01',
            'date_to' => '2026-12-31', // ~3 years - exceeds limit
        ]));

        $response->assertSuccessful();

        // Should be clamped to 455 days from date_from
        $dateFrom = Carbon::parse($response->json('meta.date_from'));
        $dateTo = Carbon::parse($response->json('meta.date_to'));

        expect($dateFrom->diffInDays($dateTo))->toBeLessThanOrEqual(455);
        expect($response->json('meta.max_range_days'))->toBe(455);
    });

    test('swaps date_from and date_to if provided in wrong order', function () {
        $response = $this->getJson(route('api.v1.tenants.calendar.index', [
            'tenant' => $this->tenant->alias,
            'date_from' => '2026-02-28',
            'date_to' => '2026-02-01',
        ]));

        $response->assertSuccessful();

        // Should have swapped the dates
        expect($response->json('meta.date_from'))->toBe('2026-02-01');
        expect($response->json('meta.date_to'))->toBe('2026-02-28');
    });

    test('excludes draft events', function () {
        $publishedEvent = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today(),
            'is_draft' => false,
        ]);

        $draftEvent = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today(),
            'is_draft' => true,
        ]);

        $response = $this->getJson(route('api.v1.tenants.calendar.index', [
            'tenant' => $this->tenant->alias,
        ]));

        $response->assertSuccessful();

        $eventIds = collect($response->json('data'))->pluck('id')->toArray();

        expect($eventIds)->toContain($publishedEvent->id);
        expect($eventIds)->not->toContain($draftEvent->id);
    });

    test('filters by tenant when all_tenants is false', function () {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

        $thisTenantsEvent = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today(),
            'is_draft' => false,
        ]);

        $otherTenantsEvent = Calendar::factory()->create([
            'tenant_id' => $otherTenant->id,
            'date' => Carbon::today(),
            'is_draft' => false,
        ]);

        $response = $this->getJson(route('api.v1.tenants.calendar.index', [
            'tenant' => $this->tenant->alias,
        ]));

        $response->assertSuccessful();

        $eventIds = collect($response->json('data'))->pluck('id')->toArray();

        expect($eventIds)->toContain($thisTenantsEvent->id);
        expect($eventIds)->not->toContain($otherTenantsEvent->id);
    });

    test('includes events from all tenants when all_tenants is true', function () {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

        $thisTenantsEvent = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today(),
            'is_draft' => false,
        ]);

        $otherTenantsEvent = Calendar::factory()->create([
            'tenant_id' => $otherTenant->id,
            'date' => Carbon::today(),
            'is_draft' => false,
        ]);

        $response = $this->getJson(route('api.v1.tenants.calendar.index', [
            'tenant' => $this->tenant->alias,
            'all_tenants' => 'true',
        ]));

        $response->assertSuccessful();

        $eventIds = collect($response->json('data'))->pluck('id')->toArray();

        expect($eventIds)->toContain($thisTenantsEvent->id);
        expect($eventIds)->toContain($otherTenantsEvent->id);
    });
});
