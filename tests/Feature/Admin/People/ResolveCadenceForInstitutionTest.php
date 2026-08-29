<?php

use App\Actions\Cadences\ResolveCadenceForInstitution;
use App\Models\Cadence;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->for(Tenant::query()->first())->create();
});

test('an institution with no terms of its own inherits the global ladder', function (): void {
    $global = Cadence::factory()->forYear(2025)->create();

    expect(ResolveCadenceForInstitution::execute($this->institution->id, Carbon::parse('2025-11-01'))?->id)
        ->toBe($global->id);
});

test('one own term stops the global ladder applying at all', function (): void {
    // The rule ResolveCadenceForDuty::pick() also enforces: a body that defines its own
    // boundaries never falls back per-year, or a missing row silently hands it someone
    // else's dates.
    Cadence::factory()->forYear(2024)->create();
    Cadence::factory()->forYear(2025)->create();
    $own = Cadence::factory()->forYear(2024)->create(['institution_id' => $this->institution->id]);

    expect(ResolveCadenceForInstitution::execute($this->institution->id, Carbon::parse('2024-11-01'))?->id)
        ->toBe($own->id)
        // 2025 exists globally but not for this body, so nothing applies.
        ->and(ResolveCadenceForInstitution::execute($this->institution->id, Carbon::parse('2025-11-01')))
        ->toBeNull();
});

test('resolution is strictly by containment — no falling forward to the next term', function (): void {
    Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);

    expect(ResolveCadenceForInstitution::execute($this->institution->id, Carbon::parse('2021-03-01')))
        ->toBeNull();
});

test('the batch variant answers for several institutions at once', function (): void {
    $other = Institution::factory()->for(Tenant::query()->first())->create();
    $global = Cadence::factory()->forYear(2025)->create();
    $own = Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);

    $resolved = ResolveCadenceForInstitution::forInstitutions(
        collect([$this->institution->id, $other->id]),
        Carbon::parse('2025-11-01'),
    );

    expect($resolved[$this->institution->id]?->id)->toBe($own->id)
        ->and($resolved[$other->id]?->id)->toBe($global->id);
});
