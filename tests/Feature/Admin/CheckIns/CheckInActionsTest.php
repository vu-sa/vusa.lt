<?php

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\States\InstitutionCheckIns\Active;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure an authenticated user with permissions (admin)
    $this->admin = makeAdminUser();
    asUserWithInertia($this->admin);
});

it('allows creating a check-in via web route', function () {
    $institution = Institution::factory()->create();

    $response = $this->post(route('institutions.check-ins.store', $institution), [
        'duration_days' => 7,
        'confidence' => 'medium',
        'mode' => 'blackout',
        'note' => 'planning break',
    ]);

    $response->assertStatus(302);
    expect(InstitutionCheckIn::query()->where('institution_id', $institution->id)->count())->toBeGreaterThan(0);
});

it('confirms and withdraws via web routes', function () {
    $checkIn = InstitutionCheckIn::factory()->create([
        'state' => Active::class,
        'until_date' => Carbon::now()->addDays(5),
    ]);

    $this->post(route('check-ins.confirm', $checkIn))->assertStatus(302);
    $this->post(route('check-ins.withdraw', $checkIn))->assertStatus(302);

    $checkIn->refresh();
    // Count is derived from verifications table; just ensure at least one verification exists
    expect($checkIn->verifications()->count())->toBeGreaterThanOrEqual(1);
});

it('supports dispute/resolve/suppress/unsuppress via web routes', function () {
    $checkIn = InstitutionCheckIn::factory()->create([
        'state' => Active::class,
        'until_date' => Carbon::now()->addDays(10),
    ]);

    $this->post(route('check-ins.dispute', $checkIn), [ 'reason' => 'not accurate' ])->assertStatus(302);
    $this->post(route('check-ins.resolve', $checkIn), [ 'resolution' => 'keep' ])->assertStatus(302);
    $this->post(route('check-ins.suppress', $checkIn), [ 'reason' => 'policy' ])->assertStatus(302);
    $this->post(route('check-ins.unsuppress', $checkIn))->assertStatus(302);
});
