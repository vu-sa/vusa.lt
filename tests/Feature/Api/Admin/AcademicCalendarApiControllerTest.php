<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('academic calendar API', function () {
    test('requires authentication', function () {
        $this->getJson(route('api.v1.admin.academicCalendar.vacations'))
            ->assertStatus(401);
    });

    test('returns vacation periods for the requested years', function () {
        $response = $this->actingAs(User::factory()->create())->getJson(
            route('api.v1.admin.academicCalendar.vacations', ['from_year' => 2025, 'to_year' => 2025])
        );

        $response->assertSuccessful()
            ->assertJsonPath('success', true);

        $periods = collect($response->json('data'));

        // Summer, winter, late January and Easter.
        expect($periods)->toHaveCount(4);

        $summer = $periods->firstWhere('type', 'summer');

        expect($summer['start'])->toBe('2025-07-01')
            ->and($summer['end'])->toBe('2025-08-31')
            ->and($periods->pluck('start')->every(fn ($start) => str_starts_with($start, '2025')))->toBeTrue();
    });

    test('defaults to the years around today', function () {
        $this->travelTo('2026-03-01');

        $response = $this->actingAs(User::factory()->create())->getJson(
            route('api.v1.admin.academicCalendar.vacations')
        );

        $years = collect($response->json('data'))
            ->map(fn (array $period) => (int) substr($period['start'], 0, 4))
            ->unique()
            ->sort()
            ->values();

        expect($years->all())->toBe([2025, 2026, 2027]);
    });

    test('clamps an excessive year span', function () {
        $response = $this->actingAs(User::factory()->create())->getJson(
            route('api.v1.admin.academicCalendar.vacations', ['from_year' => 2020, 'to_year' => 2090])
        );

        $years = collect($response->json('data'))
            ->map(fn (array $period) => (int) substr($period['start'], 0, 4))
            ->unique();

        expect($years->max())->toBe(2040);
    });

    test('rejects an invalid year', function () {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.v1.admin.academicCalendar.vacations', ['from_year' => 'not-a-year']))
            ->assertStatus(422);
    });
});
