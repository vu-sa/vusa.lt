<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['queue.default' => 'sync']);

    // Owning tenant & duty.
    $this->owningTenant = Tenant::query()->first();
    $institution = Institution::factory()->for($this->owningTenant)->create();
    $this->duty = Duty::factory()->for($institution)->create();

    // Second tenant — will be assignable.
    $this->assignableTenant = Tenant::query()->where('id', '!=', $this->owningTenant->id)->first();

    if (! $this->assignableTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    // Attach $assignableTenant with quota = 2.
    $this->duty->assignableTenants()->attach($this->assignableTenant->id, ['quota' => 2]);

    // Role that gives duties.update.padalinys.
    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.update.padalinys',
    ]);

    // Owning-tenant admin.
    $this->owningAdmin = makeUser($this->owningTenant);
    $this->owningAdmin->duties()->first()?->assignRole('Communication Coordinator');

    // Assignable-tenant admin.
    $this->crossAdmin = makeUser($this->assignableTenant);
    $this->crossAdmin->duties()->first()?->assignRole('Communication Coordinator');

    // A user belonging to the assignable tenant.
    $this->tenantUser = makeUser($this->assignableTenant);

    // A user from a completely different tenant.
    $this->outsideUser = User::factory()->create();
});

test('owning-tenant admin can update their own duty', function () {
    $response = asUser($this->owningAdmin)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => 'Atnaujinta', 'en' => 'Updated'],
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
    ]);

    $response->assertRedirect();
    expect($this->duty->fresh()->getTranslation('name', 'lt'))->toBe('Atnaujinta');
});

test('cross-tenant admin cannot update the duty itself', function () {
    $response = asUser($this->crossAdmin)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => 'Bandymas', 'en' => 'Attempt'],
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
    ]);

    expect($response->status())->toBe(403);
});

test('cross-tenant admin can add their own tenant user via batch-update', function () {
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            [
                'user_id' => $this->tenantUser->id,
                'action' => 'add',
                'start_date' => now()->toDateString(),
            ],
        ],
    ]);

    $response->assertRedirect();

    $exists = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $this->tenantUser->id)
        ->exists();

    expect($exists)->toBeTrue();
});

test('cross-tenant admin can add a user who has no prior tenant membership', function () {
    // Users have no direct tenant assignment — membership is through duties.
    // Any user (even without existing duties) may be assigned to a cross-tenant duty.
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            [
                'user_id' => $this->outsideUser->id,
                'action' => 'add',
                'start_date' => now()->toDateString(),
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $exists = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $this->outsideUser->id)
        ->exists();

    expect($exists)->toBeTrue();
});

test('completely unauthorized user cannot batch-update duty users', function () {
    $stranger = User::factory()->create();

    $response = asUser($stranger)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $this->tenantUser->id, 'action' => 'add', 'start_date' => now()->toDateString()],
        ],
    ]);

    expect($response->status())->toBe(403);
});

test('cross-tenant admin can swap users within their quota', function () {
    // Fill quota (2 slots).
    $user1 = makeUser($this->assignableTenant);
    $user2 = makeUser($this->assignableTenant);
    $user3 = makeUser($this->assignableTenant);
    $user4 = makeUser($this->assignableTenant);

    // Add 2 active dutiables to fill the quota.
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user1->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user2->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    // Remove 2 and add 2 — net change is 0, so quota should allow it.
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $user1->id, 'action' => 'remove', 'end_date' => now()->toDateString()],
            ['user_id' => $user2->id, 'action' => 'remove', 'end_date' => now()->toDateString()],
            ['user_id' => $user3->id, 'action' => 'add', 'start_date' => now()->toDateString()],
            ['user_id' => $user4->id, 'action' => 'add', 'start_date' => now()->toDateString()],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect(
        Dutiable::where('duty_id', $this->duty->id)
            ->where('dutiable_id', $user3->id)
            ->whereNull('end_date')
            ->exists()
    )->toBeTrue();

    expect(
        Dutiable::where('duty_id', $this->duty->id)
            ->where('dutiable_id', $user4->id)
            ->whereNull('end_date')
            ->exists()
    )->toBeTrue();
});

test('cross-tenant admin cannot exceed their quota', function () {
    // Fill quota (2 slots).
    $user1 = makeUser($this->assignableTenant);
    $user2 = makeUser($this->assignableTenant);
    $user3 = makeUser($this->assignableTenant);

    // Add 2 active dutiables to fill the quota (tenant_id marks them as assigned for this assignable tenant).
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user1->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user2->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    // Try to add a 3rd user — should fail.
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $user3->id, 'action' => 'add', 'start_date' => now()->toDateString()],
        ],
    ]);

    // Non-Inertia web request: validation failure redirects back with errors.
    $response->assertSessionHasErrors();

    $exists = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $user3->id)
        ->exists();

    expect($exists)->toBeFalse();
});

test('dutiable can be edited by cross-tenant admin when user belongs to their tenant', function () {
    $dutiable = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $this->tenantUser->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    $response = asUser($this->crossAdmin)->patch(route('dutiables.update', $dutiable), [
        'additional_email' => 'cross@example.com',
    ]);

    $response->assertRedirect();
    expect($dutiable->fresh()->additional_email)->toBe('cross@example.com');
});

test('cross-tenant admin cannot edit dutiable of user from another tenant', function () {
    $dutiable = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $this->outsideUser->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    $response = asUser($this->crossAdmin)->patch(route('dutiables.update', $dutiable), [
        'additional_email' => 'hack@example.com',
    ]);

    expect($response->status())->toBe(403);
});

test('cross-tenant admin can open the duty edit page (read-only mode)', function () {
    $response = asUser($this->crossAdmin)->get(route('duties.edit', $this->duty));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/People/EditDuty')
            ->where('canEditDuty', false));
});

test('owning-tenant admin gets full edit access on the duty edit page', function () {
    $response = asUser($this->owningAdmin)->get(route('duties.edit', $this->duty));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/People/EditDuty')
            ->where('canEditDuty', true));
});

test('unrelated user cannot open the duty edit page', function () {
    $stranger = User::factory()->create();

    expect(asUser($stranger)->get(route('duties.edit', $this->duty))->status())->toBe(403);
});

test('edit page exposes assignableTenantUsers map for cross-tenant admin', function () {
    $tenantUser = makeUser($this->assignableTenant);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $tenantUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    asUser($this->crossAdmin)->get(route('duties.edit', $this->duty))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('assignableTenantUsers', fn ($map) => collect($map)->has((string) $this->assignableTenant->id))
        );
});

test('edit page assignableTenantUsers excludes users end-dated today', function () {
    $tenantUser = makeUser($this->assignableTenant);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $tenantUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->toDateString(),
    ]);

    asUser($this->crossAdmin)->get(route('duties.edit', $this->duty))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('assignableTenantUsers', fn ($map) => empty($map[$this->assignableTenant->id] ?? []))
        );
});

test('edit page assignableTenantUsers excludes users end-dated yesterday', function () {
    $tenantUser = makeUser($this->assignableTenant);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $tenantUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subWeek()->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]);

    asUser($this->crossAdmin)->get(route('duties.edit', $this->duty))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('assignableTenantUsers', fn ($map) => empty($map[$this->assignableTenant->id] ?? []))
        );
});

test('edit page assignableTenantUsers includes users end-dated tomorrow', function () {
    $tenantUser = makeUser($this->assignableTenant);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $tenantUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
    ]);

    asUser($this->crossAdmin)->get(route('duties.edit', $this->duty))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('assignableTenantUsers', fn ($map) => in_array($tenantUser->id, $map[$this->assignableTenant->id] ?? []))
        );
});

test('edit page assignableTenantUsers includes users with null end_date', function () {
    $tenantUser = makeUser($this->assignableTenant);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $tenantUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    asUser($this->crossAdmin)->get(route('duties.edit', $this->duty))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('assignableTenantUsers', fn ($map) => in_array($tenantUser->id, $map[$this->assignableTenant->id] ?? []))
        );
});

test('batch update quota allows adding user after end-dating one today', function () {
    // Fill quota (2 slots).
    $user1 = makeUser($this->assignableTenant);
    $user2 = makeUser($this->assignableTenant);
    $user3 = makeUser($this->assignableTenant);

    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user1->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user2->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    // End-date one user today and add a new one — net count stays at 2.
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $user2->id, 'action' => 'remove', 'end_date' => now()->toDateString()],
            ['user_id' => $user3->id, 'action' => 'add', 'start_date' => now()->toDateString()],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    // user3 should now be attached and active
    expect(
        Dutiable::where('duty_id', $this->duty->id)
            ->where('dutiable_id', $user3->id)
            ->whereNull('end_date')
            ->exists()
    )->toBeTrue();

    // user2 should be end-dated
    expect(
        Dutiable::where('duty_id', $this->duty->id)
            ->where('dutiable_id', $user2->id)
            ->whereNotNull('end_date')
            ->exists()
    )->toBeTrue();
});

test('batch update quota still blocks when net count exceeds quota after swaps', function () {
    // Fill quota (2 slots).
    $user1 = makeUser($this->assignableTenant);
    $user2 = makeUser($this->assignableTenant);
    $user3 = makeUser($this->assignableTenant);
    $user4 = makeUser($this->assignableTenant);

    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user1->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user2->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    // Try to remove 1 and add 2 — net count would be 3, exceeding quota of 2.
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $user2->id, 'action' => 'remove', 'end_date' => now()->toDateString()],
            ['user_id' => $user3->id, 'action' => 'add', 'start_date' => now()->toDateString()],
            ['user_id' => $user4->id, 'action' => 'add', 'start_date' => now()->toDateString()],
        ],
    ]);

    $response->assertSessionHasErrors();

    expect(
        Dutiable::where('duty_id', $this->duty->id)
            ->where('dutiable_id', $user3->id)
            ->exists()
    )->toBeFalse();
});

test('batch update quota counts user end-dated yesterday as already removed', function () {
    $user1 = makeUser($this->assignableTenant);
    $user2 = makeUser($this->assignableTenant);
    $user3 = makeUser($this->assignableTenant);

    // One active, one already end-dated yesterday.
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user1->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user2->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subWeek()->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]);

    // Current active count = 1. Adding user3 makes it 2, which is within quota.
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $user3->id, 'action' => 'add', 'start_date' => now()->toDateString()],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('owning admin update with user_ids creates cross-tenant dutiables with tenant_id', function () {
    $crossUser = makeUser($this->assignableTenant);

    asUser($this->owningAdmin)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => $this->duty->getTranslation('name', 'lt'), 'en' => $this->duty->getTranslation('name', 'en') ?? ''],
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'assignable_tenants' => [
            ['tenant_id' => $this->assignableTenant->id, 'quota' => 2, 'user_ids' => [$crossUser->id]],
        ],
    ])->assertRedirect();

    $dutiable = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $crossUser->id)
        ->first();

    expect($dutiable)->not->toBeNull()
        ->and($dutiable->tenant_id)->toBe($this->assignableTenant->id);
});

test('removing a tenant from assignable_tenants end-dates their active reps', function () {
    $crossUser = makeUser($this->assignableTenant);
    $dutiable = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $crossUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    // Submit update with no assignable_tenants (tenant removed).
    asUser($this->owningAdmin)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => $this->duty->getTranslation('name', 'lt'), 'en' => $this->duty->getTranslation('name', 'en') ?? ''],
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'assignable_tenants' => [],
    ])->assertRedirect();

    expect($dutiable->fresh()->end_date)->not->toBeNull();
});

test('owning-tenant TransferList update does not touch cross-tenant reps', function () {
    $ownUser = makeUser($this->owningTenant);
    $crossUser = makeUser($this->assignableTenant);

    // Create a cross-tenant rep (tenant_id set).
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $crossUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);

    // Owning admin submits current_users = [ownUser] (crossUser not in current_users).
    // The cross-tenant rep must not be end-dated by the TransferList logic.
    asUser($this->owningAdmin)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => $this->duty->getTranslation('name', 'lt'), 'en' => $this->duty->getTranslation('name', 'en') ?? ''],
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'current_users' => [$ownUser->id],
        // Keep cross-tenant user in user_ids so the per-tenant sync doesn't remove them.
        'assignable_tenants' => [
            ['tenant_id' => $this->assignableTenant->id, 'quota' => 2, 'user_ids' => [$crossUser->id]],
        ],
    ])->assertRedirect();

    // The owning-tenant dutiable for ownUser was created.
    $ownDutiable = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $ownUser->id)
        ->whereNull('tenant_id')
        ->first();
    expect($ownDutiable)->not->toBeNull();

    // The cross-tenant dutiable is still active (not end-dated by the TransferList path).
    $crossDutiable = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $crossUser->id)
        ->where('tenant_id', $this->assignableTenant->id)
        ->first();
    expect($crossDutiable->end_date)->toBeNull();
});

test('batchUpdateUsers can remove user with future end_date', function () {
    $crossUser = makeUser($this->assignableTenant);

    // Simulate a wizard-added user with a future end_date.
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $crossUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]);

    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $crossUser->id, 'action' => 'remove', 'end_date' => now()->toDateString()],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $dutiable = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $crossUser->id)
        ->where('tenant_id', $this->assignableTenant->id)
        ->first();

    expect($dutiable->end_date)->not->toBeNull();
});

test('batchUpdateUsers quota allows swap when removing future-end-dated user', function () {
    $user1 = makeUser($this->assignableTenant);
    $user2 = makeUser($this->assignableTenant);
    $user3 = makeUser($this->assignableTenant);

    // Fill quota (2 slots). user1 has null end_date, user2 has future end_date.
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user1->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
    ]);
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $user2->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]);

    // Remove future-end-dated user2 and add user3 — net count stays at 2.
    $response = asUser($this->crossAdmin)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $user2->id, 'action' => 'remove', 'end_date' => now()->toDateString()],
            ['user_id' => $user3->id, 'action' => 'add', 'start_date' => now()->toDateString()],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect(
        Dutiable::where('duty_id', $this->duty->id)
            ->where('dutiable_id', $user3->id)
            ->whereNull('end_date')
            ->exists()
    )->toBeTrue();
});

test('owning admin can remove wizard-added cross-tenant user via duties.update', function () {
    $crossUser = makeUser($this->assignableTenant);

    // Wizard adds user with a future end_date.
    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $crossUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]);

    // Owning admin removes the cross-tenant user by submitting empty user_ids.
    asUser($this->owningAdmin)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => $this->duty->getTranslation('name', 'lt'), 'en' => $this->duty->getTranslation('name', 'en') ?? ''],
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'assignable_tenants' => [
            ['tenant_id' => $this->assignableTenant->id, 'quota' => 2, 'user_ids' => []],
        ],
    ])->assertRedirect();

    $dutiable = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $crossUser->id)
        ->where('tenant_id', $this->assignableTenant->id)
        ->first();

    expect($dutiable->end_date)->not->toBeNull();
});

test('removing assignable tenant end-dates all active reps including future end_dated ones', function () {
    $crossUser = makeUser($this->assignableTenant);

    Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $crossUser->id,
        'dutiable_type' => User::class,
        'tenant_id' => $this->assignableTenant->id,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]);

    // Remove the assignable tenant entirely.
    asUser($this->owningAdmin)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => $this->duty->getTranslation('name', 'lt'), 'en' => $this->duty->getTranslation('name', 'en') ?? ''],
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'assignable_tenants' => [],
    ])->assertRedirect();

    $dutiable = Dutiable::where('duty_id', $this->duty->id)
        ->where('dutiable_id', $crossUser->id)
        ->where('tenant_id', $this->assignableTenant->id)
        ->first();

    expect($dutiable->end_date)->not->toBeNull();
});
