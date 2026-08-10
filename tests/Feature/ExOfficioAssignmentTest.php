<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * End-to-end coverage for ex-officio assignment through the real admin routes.
 *
 * ExOfficioSyncTest drives SyncExOfficioDutiables directly, which proves the
 * listener's own logic but not that anything reaches it. Every regression here
 * was a controller or model-layer path that silently never dispatched
 * DutiableChanged, so the listener was correct and the feature still broke.
 */
pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['queue.default' => 'sync']);

    $this->tenant = Tenant::query()->first();
    $institution = Institution::factory()->for($this->tenant)->create();
    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);

    $this->superAdmin = makeAdminUser($this->tenant);
});

/** Create an active membership on the source duty, which should grant the target ex officio. */
function grantSourceDuty(Duty $duty, User $user): Dutiable
{
    return Dutiable::create([
        'duty_id' => $duty->id,
        'dutiable_id' => $user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay(),
    ]);
}

test('batch-update-users grants the ex-officio target duty', function (): void {
    $user = User::factory()->create();

    asUser($this->superAdmin)->post(route('duties.batchUpdateUsers', $this->sourceDuty), [
        'user_changes' => [
            ['user_id' => $user->id, 'action' => 'add', 'start_date' => now()->subDay()->toDateString()],
        ],
    ])->assertRedirect();

    $derived = Dutiable::where('duty_id', $this->targetDuty->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->dutiable_id)->toBe($user->id)
        ->and($derived->via_dutiable_id)->not->toBeNull();
});

test('the duty edit form grants the ex-officio target duty', function (): void {
    $user = User::factory()->create();

    asUser($this->superAdmin)->patch(route('duties.update', $this->sourceDuty), [
        'name' => ['lt' => 'Pirmininkas', 'en' => 'Chairperson'],
        'institution_id' => $this->sourceDuty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'current_users' => [$user->id],
        // DutyForm.vue always round-trips the existing links; omitting them is an
        // instruction to drop the ex-officio relationship, not a neutral save.
        'ex_officio_target_duty_ids' => [$this->targetDuty->id],
    ])->assertRedirect();

    expect(Dutiable::where('duty_id', $this->targetDuty->id)->count())->toBe(1);
});

test('saving the target duty does not end-date its ex-officio members', function (): void {
    $user = User::factory()->create();
    grantSourceDuty($this->sourceDuty, $user);

    $derived = Dutiable::where('duty_id', $this->targetDuty->id)->firstOrFail();

    // DutyForm.vue filters ex-officio rows out of `current_users` before posting,
    // so an untouched save of the target duty arrives with an empty member list.
    asUser($this->superAdmin)->patch(route('duties.update', $this->targetDuty), [
        'name' => ['lt' => 'Narys', 'en' => 'Member'],
        'institution_id' => $this->targetDuty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'current_users' => [],
    ])->assertRedirect();

    expect($derived->fresh()->end_date)->toBeNull()
        ->and($this->targetDuty->current_users()->count())->toBe(1);
});

test('removing a member from the source duty end-dates the derived row', function (): void {
    $user = User::factory()->create();
    $source = grantSourceDuty($this->sourceDuty, $user);

    asUser($this->superAdmin)->post(route('duties.batchUpdateUsers', $this->sourceDuty), [
        'user_changes' => [
            ['user_id' => $user->id, 'action' => 'remove'],
        ],
    ])->assertRedirect();

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived?->end_date)->not->toBeNull()
        ->and($derived->end_date->toDateString())->toBe($source->fresh()->end_date->toDateString());
});

test('deleting the source dutiable removes the derived row', function (): void {
    $user = User::factory()->create();
    $source = grantSourceDuty($this->sourceDuty, $user);

    expect(Dutiable::where('duty_id', $this->targetDuty->id)->count())->toBe(1);

    asUser($this->superAdmin)->delete(route('dutiables.destroy', $source));

    // The `via_dutiable_id` foreign key is nullOnDelete(), so a derived row that
    // outlives its source keeps granting permissions while looking manual.
    expect(Dutiable::where('duty_id', $this->targetDuty->id)->count())->toBe(0);
});

test('an admin assigning themselves still triggers the ex-officio sync', function (): void {
    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo(['duties.read.padalinys', 'duties.update.padalinys']);

    $admin = makeTenantUser('Communication Coordinator', $this->tenant);

    // Touching their own membership routes the mutation through AccessChangeAnalyzer,
    // which intercepts DutiableChanged to keep speculative listeners off uncommitted
    // state — the events still have to be replayed once the change is kept.
    asUser($admin)->post(route('duties.batchUpdateUsers', $this->sourceDuty), [
        'user_changes' => [
            ['user_id' => $admin->id, 'action' => 'add', 'start_date' => now()->subDay()->toDateString()],
        ],
    ])->assertRedirect();

    expect(Dutiable::where('duty_id', $this->targetDuty->id)->where('dutiable_id', $admin->id)->count())->toBe(1);
});
