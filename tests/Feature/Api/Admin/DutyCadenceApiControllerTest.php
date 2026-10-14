<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-09-20');

    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create();
});

afterEach(fn () => Carbon::setTestNow());

test('lists the global terms newest first and marks the current one', function (): void {
    Cadence::factory()->forYear(2025)->create();
    Cadence::factory()->forYear(2026)->create();

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.duties.cadences', $this->duty))
        ->assertOk();

    expect($response->json('data.*.start_date'))->toBe(['2026-07-01', '2025-07-01'])
        ->and($response->json('data.*.is_current'))->toBe([true, false])
        ->and($response->json('data.0.label'))->toBe('2026–2027');
});

test('an institution with its own terms does not get the global ladder', function (): void {
    Cadence::factory()->forYear(2026)->create();
    Cadence::factory()->create([
        'institution_id' => $this->institution->id,
        'start_date' => '2026-05-18',
        'end_date' => '2027-05-17',
    ]);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.duties.cadences', $this->duty))
        ->assertOk();

    expect($response->json('data.*.start_date'))->toBe(['2026-05-18']);
});

test('someone who cannot staff the duty is refused', function (): void {
    asUser(makeUser($this->tenant))
        ->getJson(route('api.v1.admin.duties.cadences', $this->duty))
        ->assertForbidden();
});

test('an admin of another tenant is refused', function (): void {
    $otherAdmin = makeTenantUserWithRole('Komunikacijos koordinatorius', Tenant::query()->whereKeyNot($this->tenant->id)->first());

    asUser($otherAdmin)
        ->getJson(route('api.v1.admin.duties.cadences', $this->duty))
        ->assertForbidden();
});
