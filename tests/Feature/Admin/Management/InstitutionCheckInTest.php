<?php

use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('allows creating check-in with past start date', function () {
    $tenant = Tenant::factory()->create();
    $user = makeAdminUser($tenant);
    $institution = Institution::factory()->for($tenant)->create();

    $startDateTime = now()->subMonths(6)->startOfDay();
    $endDateTime = now()->subMonths(5)->startOfDay();
    $startDate = $startDateTime->toDateString();
    $endDate = $endDateTime->toDateString();

    $response = asUser($user)->post(route('institutions.check-ins.store', $institution), [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'note' => 'Test past check-in',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('institution_check_ins', [
        'institution_id' => $institution->id,
        'user_id' => $user->id,
        'start_date' => $startDateTime->toDateTimeString(),
        'end_date' => $endDateTime->toDateTimeString(),
    ]);
});

test('rejects check-in end date beyond three months', function () {
    $tenant = Tenant::factory()->create();
    $user = makeAdminUser($tenant);
    $institution = Institution::factory()->for($tenant)->create();

    $startDate = now()->toDateString();
    $endDate = now()->addMonths(3)->addDay()->toDateString();

    $response = asUser($user)->post(route('institutions.check-ins.store', $institution), [
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $response->assertSessionHasErrors(['end_date']);

    $this->assertDatabaseMissing('institution_check_ins', [
        'institution_id' => $institution->id,
        'user_id' => $user->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
});
