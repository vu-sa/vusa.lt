<?php

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

describe('cadence resolution', function (): void {
    it('prefers an institution override over the global ladder', function (): void {
        $institution = Institution::factory()->create();
        $duty = Duty::factory()->for($institution)->create();

        Cadence::factory()->forYear(2025)->create();
        $override = Cadence::factory()->forYear(2025)->create([
            'institution_id' => $institution->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ]);

        $resolved = ResolveCadenceForDuty::execute($duty, Carbon::parse('2025-09-01'));

        expect($resolved?->id)->toBe($override->id);
    });

    it('falls back to the global ladder when the institution has no cadences', function (): void {
        $duty = Duty::factory()->for(Institution::factory())->create();
        $global = Cadence::factory()->forYear(2025)->create();

        $resolved = ResolveCadenceForDuty::execute($duty, Carbon::parse('2025-09-01'));

        expect($resolved?->id)->toBe($global->id);
    });

    it('returns null when no cadence exists at all', function (): void {
        $duty = Duty::factory()->for(Institution::factory())->create();

        expect(ResolveCadenceForDuty::execute($duty))->toBeNull();
    });

    it('picks the term containing the reference date', function (): void {
        $duty = Duty::factory()->for(Institution::factory())->create();
        Cadence::factory()->forYear(2024)->create();
        $current = Cadence::factory()->forYear(2025)->create();
        Cadence::factory()->forYear(2026)->create();

        $resolved = ResolveCadenceForDuty::execute($duty, Carbon::parse('2026-01-15'));

        expect($resolved?->id)->toBe($current->id);
    });

    it('falls forward to the next upcoming term when the date precedes every cadence', function (): void {
        $duty = Duty::factory()->for(Institution::factory())->create();
        $first = Cadence::factory()->forYear(2025)->create();
        Cadence::factory()->forYear(2026)->create();

        $resolved = ResolveCadenceForDuty::execute($duty, Carbon::parse('2020-01-01'));

        expect($resolved?->id)->toBe($first->id);
    });

    it('falls back to the latest past term when the date follows every cadence', function (): void {
        $duty = Duty::factory()->for(Institution::factory())->create();
        Cadence::factory()->forYear(2024)->create();
        $latest = Cadence::factory()->forYear(2025)->create();

        $resolved = ResolveCadenceForDuty::execute($duty, Carbon::parse('2099-01-01'));

        expect($resolved?->id)->toBe($latest->id);
    });

    it('resolves a batch of duties in a single cadence query', function (): void {
        $institution = Institution::factory()->create();
        $duties = Duty::factory()->count(3)->for($institution)->create();
        Cadence::factory()->forYear(2025)->create();

        DB::enableQueryLog();
        $resolved = ResolveCadenceForDuty::forDuties($duties, Carbon::parse('2025-09-01'));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($resolved)->toHaveCount(3)
            ->and($queries)->toHaveCount(1);
    });
});

describe('dutiable scopes', function (): void {
    it('counts a row ending today as current but a future start as not yet active', function (): void {
        $duty = Duty::factory()->for(Institution::factory())->create();
        $user = User::factory()->create();

        $endingToday = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'dutiable_id' => $user->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $startsTomorrow = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'dutiable_id' => User::factory()->create()->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => null,
        ]);

        $current = Dutiable::query()->current()->pluck('id');
        $activeToday = Dutiable::query()->activeOn()->pluck('id');

        // current() is seat-allocation semantics: a future start already holds the seat.
        expect($current)->toContain($endingToday->id)->toContain($startsTomorrow->id);

        // activeOn() is point-in-time: the future start is not in force yet.
        expect($activeToday)->toContain($endingToday->id)->not->toContain($startsTomorrow->id);
    });
});
