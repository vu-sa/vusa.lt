<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * A user's tenants are derived from their duties, and any tenant admin can attach
 * any person to one of their own duties. Because AuthController::callback resolves
 * the Microsoft account by users.email, an unrestricted email edit is an account
 * takeover. Email changes therefore require full tenant containment, while a name
 * change is reserved for super administrators.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
});

/** Give the user an additional duty in a newly created, unrelated tenant. */
function giveDutyInOtherTenant(User $user): Tenant
{
    $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);

    $user->duties()->attach(
        Duty::factory()->for(Institution::factory()->for($otherTenant))->create(),
        ['start_date' => now()->subDay()],
    );

    return $otherTenant;
}

/** The payload the edit form actually sends: every field, identity included. */
function identityPayload(User $target, array $overrides = []): array
{
    return array_merge([
        'name' => $target->name,
        'email' => $target->email,
        'phone' => $target->phone,
        'current_duties' => $target->current_duties()->pluck('duties.id')->all(),
    ], $overrides);
}

test('coordinator cannot change the email of a user who also belongs to another tenant', function (): void {
    $target = makeUser($this->tenant);
    giveDutyInOtherTenant($target);

    asUser($this->coordinator)
        ->patch(route('users.update', $target), identityPayload($target, [
            'email' => 'taken.over@attacker.test',
        ]))
        ->assertSessionHasErrors('email');

    expect($target->fresh()->email)->not->toBe('taken.over@attacker.test');
});

test('coordinator cannot change the name of a user wholly within their tenant', function (): void {
    $target = makeUser($this->tenant);

    asUser($this->coordinator)
        ->patch(route('users.update', $target), identityPayload($target, [
            'name' => 'Renamed Person',
        ]))
        ->assertSessionHasErrors('name');

    expect($target->fresh()->name)->not->toBe('Renamed Person');
});

test('coordinator cannot change their own name through user administration', function (): void {
    asUser($this->coordinator)
        ->patch(route('users.update', $this->coordinator), identityPayload($this->coordinator, [
            'name' => 'Renamed Coordinator',
        ]))
        ->assertSessionHasErrors('name');

    expect($this->coordinator->fresh()->name)->not->toBe('Renamed Coordinator');
});

test('coordinator can still edit non-identity fields of a multi-tenant user', function (): void {
    // Also the regression guard for the form always posting name and email whether
    // or not they changed — an unchanged identity field must not 422 the save.
    $target = makeUser($this->tenant);
    giveDutyInOtherTenant($target);

    asUser($this->coordinator)
        ->patch(route('users.update', $target), identityPayload($target, [
            'phone' => '+370 600 00000',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->fresh()->phone)->toBe('+370 600 00000');
});

test('coordinator cannot change the email of a super admin attached to their own tenant', function (): void {
    // The exploit in full: pull a super admin into your tenant via a duty, then
    // rewrite the address they sign in with.
    $target = makeUser($this->tenant);
    $target->assignRole(config('permission.super_admin_role_name'));

    asUser($this->coordinator)
        ->patch(route('users.update', $target), identityPayload($target, [
            'email' => 'taken.over@attacker.test',
        ]))
        ->assertSessionHasErrors('email');

    expect($target->fresh()->email)->not->toBe('taken.over@attacker.test');
});

test('a non-super admin with global user update permission cannot change a super admin email', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'users.update.*', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Global User Manager', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $globalUpdater = makeTenantUserWithRole($role->name, $this->tenant);
    $target = makeAdminUser($this->tenant);

    asUser($globalUpdater)
        ->patch(route('users.update', $target), identityPayload($target, [
            'email' => 'taken.over@attacker.test',
        ]))
        ->assertSessionHasErrors('email');

    expect($target->fresh()->email)->not->toBe('taken.over@attacker.test');
});

test('coordinator cannot change the email of any user holding a directly assigned role', function (): void {
    $target = makeUser($this->tenant);
    $target->assignRole(Role::firstOrCreate(['name' => 'Directly Assigned', 'guard_name' => 'web']));

    asUser($this->coordinator)
        ->patch(route('users.update', $target), identityPayload($target, [
            'email' => 'nope@attacker.test',
        ]))
        ->assertSessionHasErrors('email');
});

test('coordinator can change the email of a fully contained user with no direct role', function (): void {
    $target = makeUser($this->tenant);

    asUser($this->coordinator)
        ->patch(route('users.update', $target), identityPayload($target, [
            'email' => 'new.address@stud.vu.lt',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->fresh()->email)->toBe('new.address@stud.vu.lt');
});

test('a super admin can change any email', function (): void {
    $admin = makeAdminUser($this->tenant);
    $target = makeUser($this->tenant);
    giveDutyInOtherTenant($target);

    asUser($admin)
        ->patch(route('users.update', $target), identityPayload($target, [
            'email' => 'central.office@vusa.lt',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->fresh()->email)->toBe('central.office@vusa.lt');
});

test('a super admin can change another super admin email', function (): void {
    $admin = makeAdminUser($this->tenant);
    $target = makeAdminUser($this->tenant);

    asUser($admin)
        ->patch(route('users.update', $target), identityPayload($target, [
            'email' => 'central.admin@vusa.lt',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->fresh()->email)->toBe('central.admin@vusa.lt');
});

test('a super admin can change a user name', function (): void {
    $admin = makeAdminUser($this->tenant);
    $target = makeUser($this->tenant);

    asUser($admin)
        ->patch(route('users.update', $target), identityPayload($target, [
            'name' => 'Corrected Person',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($target->fresh()->name)->toBe('Corrected Person');
});

test('a user can always change their own email', function (): void {
    giveDutyInOtherTenant($this->coordinator);

    asUser($this->coordinator)
        ->patch(route('users.update', $this->coordinator), identityPayload($this->coordinator, [
            'email' => 'my.own@stud.vu.lt',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($this->coordinator->fresh()->email)->toBe('my.own@stud.vu.lt');
});

describe('duty assignment is bounded by the actor\'s tenants', function (): void {
    test('adding a duty from another tenant is refused', function (): void {
        $target = makeUser($this->tenant);
        $foreignDuty = Duty::factory()->for(
            Institution::factory()->for(Tenant::factory()->create(['type' => 'padalinys']))
        )->create();

        asUser($this->coordinator)
            ->patch(route('users.update', $target), identityPayload($target, [
                'current_duties' => [...$target->current_duties()->pluck('duties.id')->all(), $foreignDuty->id],
            ]))
            ->assertSessionHasErrors('current_duties');

        expect($target->fresh()->duties()->whereKey($foreignDuty->id)->exists())->toBeFalse();
    });

    test('leaving an existing out-of-tenant duty untouched still saves', function (): void {
        // The form posts every duty the user holds, including ones the coordinator
        // does not administer. Only the diff may be validated.
        $target = makeUser($this->tenant);
        giveDutyInOtherTenant($target);

        asUser($this->coordinator)
            ->patch(route('users.update', $target), identityPayload($target, [
                'phone' => '+370 611 11111',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($target->fresh()->phone)->toBe('+370 611 11111');
    });

    test('creating a user with a duty from another tenant is refused', function (): void {
        $foreignDuty = Duty::factory()->for(
            Institution::factory()->for(Tenant::factory()->create(['type' => 'padalinys']))
        )->create();

        asUser($this->coordinator)
            ->post(route('users.store'), [
                'name' => 'Smuggled In',
                'email' => 'smuggled@stud.vu.lt',
                'current_duties' => [$foreignDuty->id],
            ])
            ->assertSessionHasErrors('current_duties');

        $this->assertDatabaseMissing('users', ['email' => 'smuggled@stud.vu.lt']);
    });
});
