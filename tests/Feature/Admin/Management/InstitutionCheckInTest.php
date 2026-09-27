<?php

use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('allows creating check-in with past start date', function (): void {
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

test('rejects check-in end date beyond three months', function (): void {
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

test('a coordinator can clear active reports for the institution they coordinate', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create();
    $coordinator = makeUser($tenant);
    $duty = $coordinator->duties()->firstOrFail();
    DutyResponsibility::factory()->for($duty)->forInstitution($institution)->create();
    $report = InstitutionCheckIn::factory()->for($institution)->create([
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    asUser($coordinator)->delete(route('institutions.check-ins.destroyActive', $institution))->assertRedirect();

    expect($report->fresh())->toBeNull();
});

test('institution update permission in another padalinys cannot clear reports here', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $otherTenant = Tenant::query()->whereKeyNot($tenant->id)->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create();
    $outsider = makeTenantUserWithRole('Komunikacijos koordinatorius', $otherTenant);
    $report = InstitutionCheckIn::factory()->for($institution)->create([
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    asUser($outsider)->delete(route('institutions.check-ins.destroyActive', $institution))->assertForbidden();

    expect($report->fresh())->not->toBeNull();
});

test('a member clears only their own active report', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create();
    $duty = Duty::factory()->for($institution)->create();
    $member = makeUser($tenant);
    $member->duties()->attach($duty, ['start_date' => now()->subMonth()->toDateString()]);
    $own = InstitutionCheckIn::factory()->for($institution)->for($member)->create([
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ]);
    $other = InstitutionCheckIn::factory()->for($institution)->create([
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    asUser($member)->delete(route('institutions.check-ins.destroyActive', $institution))->assertRedirect();

    expect($own->fresh())->toBeNull()
        ->and($other->fresh())->not->toBeNull();

    asUser($member)->delete(route('institutions.check-ins.destroyActive', $institution))->assertForbidden();
});

test('a member reports a period without meetings, an outsider cannot', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $member = makeUser($tenant);
    $institution = $member->duties()->firstOrFail()->institution;
    $outsider = makeUser($tenant);
    $period = ['start_date' => now()->toDateString(), 'end_date' => now()->addMonth()->toDateString()];

    asUser($outsider)->post(route('institutions.check-ins.store', $institution), $period)->assertForbidden();
    asUser($member)->post(route('institutions.check-ins.store', $institution), $period)->assertSessionHasNoErrors();

    expect($institution->checkIns()->sole()->user_id)->toBe($member->id);
});

test('a check-in must end after it starts', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $member = makeUser($tenant);

    asUser($member)
        ->post(route('institutions.check-ins.store', $member->duties()->firstOrFail()->institution), [
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ])
        ->assertSessionHasErrors('end_date');
});

test('recording a meeting inside a reported period ends the report the day before, or removes it', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $institution = Institution::factory()->for($tenant)->create();
    $shortened = InstitutionCheckIn::factory()->for($institution)->create([
        'start_date' => now()->subDays(10)->toDateString(),
        'end_date' => now()->addDays(20)->toDateString(),
    ]);
    $meetingDate = now()->addDays(5)->setTime(15, 0);

    asUser($coordinator)
        ->post(route('meetings.store'), ['start_time' => $meetingDate->toDateTimeString(), 'institution_id' => $institution->id])
        ->assertSessionHasNoErrors();

    expect($shortened->fresh()->end_date->toDateString())->toBe($meetingDate->copy()->subDay()->toDateString());

    $removed = InstitutionCheckIn::factory()->for($institution)->create([
        'start_date' => now()->addDays(30)->toDateString(),
        'end_date' => now()->addDays(60)->toDateString(),
    ]);

    asUser($coordinator)
        ->post(route('meetings.store'), ['start_time' => now()->addDays(30)->startOfDay()->toDateTimeString(), 'institution_id' => $institution->id])
        ->assertSessionHasNoErrors();

    expect($removed->fresh())->toBeNull();
});
