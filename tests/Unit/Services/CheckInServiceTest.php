<?php

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\User;
use App\Services\CheckInService;
use App\States\InstitutionCheckIns\Active;
use App\States\InstitutionCheckIns\AdminSuppressed;
use App\States\InstitutionCheckIns\Disputed;
use App\States\InstitutionCheckIns\Expired;
use App\States\InstitutionCheckIns\Invalidated;
use App\States\InstitutionCheckIns\Withdrawn;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an active blackout check-in', function () {
    $service = app(CheckInService::class);
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $until = Carbon::now()->addDays(14);

    $checkIn = $service->create($user, $institution, $until, 'medium', 'note', 'blackout');

    expect($checkIn)->toBeInstanceOf(InstitutionCheckIn::class)
        ->and($checkIn->institution_id)->toBe($institution->id)
        ->and($checkIn->user_id)->toBe($user->id)
        ->and(class_basename($checkIn->state))->toBe(class_basename(Active::class))
        ->and($checkIn->mode)->toBe('blackout')
        ->and($checkIn->until_date->toDateString())->toBe($until->toDateString());
});

it('confirm is a no-op in service (controllers handle idempotent verifications)', function () {
    $service = app(CheckInService::class);
    $checkIn = InstitutionCheckIn::factory()->blackout()->create();
    $service->confirm($checkIn);
    expect(true)->toBeTrue();
});

it('withdraws an active check-in', function () {
    $service = app(CheckInService::class);
    $checkIn = InstitutionCheckIn::factory()->blackout()->create();

    $service->withdraw($checkIn);
    $checkIn->refresh();

    expect(class_basename($checkIn->state))->toBe(class_basename(Withdrawn::class));
});

it('disputes and resolves (keep or withdraw)', function () {
    $service = app(CheckInService::class);
    $checkIn = InstitutionCheckIn::factory()->blackout()->create();
    $peer = User::factory()->create();

    $service->dispute($checkIn, $peer, 'reason');
    $checkIn->refresh();
    expect(class_basename($checkIn->state))->toBe(class_basename(Disputed::class));

    // resolve keep
    $service->resolve($checkIn, 'keep');
    $checkIn->refresh();
    expect(class_basename($checkIn->state))->toBe(class_basename(Active::class))
        ->and($checkIn->disputed_by_user_id)->toBeNull();

    // dispute and resolve withdraw
    $service->dispute($checkIn, $peer, null);
    $service->resolve($checkIn, 'withdraw');
    $checkIn->refresh();
    expect(class_basename($checkIn->state))->toBe(class_basename(Withdrawn::class));
});

it('suppresses and unsuppresses', function () {
    $service = app(CheckInService::class);
    $checkIn = InstitutionCheckIn::factory()->blackout()->create();
    $admin = User::factory()->create();

    $service->suppress($checkIn, $admin, 'policy reason');
    $checkIn->refresh();
    expect(class_basename($checkIn->state))->toBe(class_basename(AdminSuppressed::class))
        ->and($checkIn->suppressed_by_user_id)->toBe($admin->id)
        ->and($checkIn->suppressed_reason)->toBe('policy reason');

    $service->unsuppress($checkIn);
    $checkIn->refresh();
    expect(class_basename($checkIn->state))->toBe(class_basename(Active::class))
        ->and($checkIn->suppressed_by_user_id)->toBeNull();
});

it('invalidates by meeting overlap', function () {
    $service = app(CheckInService::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $checkIn = InstitutionCheckIn::factory()->create([
        'institution_id' => $institution->id,
        'user_id' => $user->id,
        'until_date' => Carbon::now()->addDays(10),
        'state' => Active::class,
    ]);

    $meeting = \App\Models\Meeting::factory()->create([
        'start_time' => Carbon::now()->addDays(5),
    ]);
    $meeting->institutions()->attach($institution->id);

    $service->invalidateByMeeting($meeting);
    $checkIn->refresh();

    expect(class_basename($checkIn->state))->toBe(class_basename(Invalidated::class))
        ->and($checkIn->invalidated_by_meeting_id)->toBe($meeting->id);
});

it('expires stale actives', function () {
    $service = app(CheckInService::class);
    $activePast = InstitutionCheckIn::factory()->create([
        'until_date' => Carbon::now()->subDay(),
        'state' => Active::class,
    ]);
    $activeFuture = InstitutionCheckIn::factory()->create([
        'until_date' => Carbon::now()->addDay(),
        'state' => Active::class,
    ]);

    $count = $service->expireStale();

    $activePast->refresh();
    $activeFuture->refresh();

    expect($count)->toBeGreaterThanOrEqual(1)
        ->and(class_basename($activePast->state))->toBe(class_basename(Expired::class))
        ->and(class_basename($activeFuture->state))->toBe(class_basename(Active::class));
});
