<?php

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'users.read.padalinys',
        'users.update.padalinys',
    ]);

    $this->admin = makeUser($this->tenant);
    $this->admin->duties()->first()->assignRole('Communication Coordinator');
});

test('admin removing own last duty is warned and nothing persists', function () {
    asUserWithInertia($this->admin)
        ->patch(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'current_duties' => [],
        ])
        ->assertSessionHas('access_change_warning');

    expect($this->admin->current_duties()->count())->toBeGreaterThan(0);
});

test('acknowledged removal persists and lands on the dashboard', function () {
    asUserWithInertia($this->admin)
        ->patch(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'current_duties' => [],
            'acknowledge_access_change' => true,
        ])
        ->assertRedirect(route('dashboard')) // not back() — the edit page would now 403
        ->assertSessionHas('success');

    expect($this->admin->current_duties()->count())->toBe(0);
});

test('editing another user is not guarded', function () {
    $other = makeUser($this->tenant);

    asUserWithInertia($this->admin)
        ->patch(route('users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'current_duties' => [],
        ])
        ->assertSessionMissing('access_change_warning');

    expect($other->current_duties()->count())->toBe(0);
});

test('super admin removing own super admin role is warned', function () {
    $superAdmin = makeUser($this->tenant);
    $superAdmin->assignRole(config('permission.super_admin_role_name'));

    asUserWithInertia($superAdmin)
        ->patch(route('users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'current_duties' => $superAdmin->current_duties->pluck('id')->all(),
            'roles' => [],
        ])
        ->assertSessionHas('access_change_warning');

    expect($superAdmin->fresh()->isSuperAdmin())->toBeTrue();
});

test('super admin removing only a duty (not the super admin role) is not warned', function () {
    $superAdmin = makeUser($this->tenant);
    $superAdmin->assignRole(config('permission.super_admin_role_name'));
    $superAdmin->duties()->first()->assignRole('Communication Coordinator');

    $superRoleId = Role::where('name', config('permission.super_admin_role_name'))->value('id');

    asUserWithInertia($superAdmin)
        ->patch(route('users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'current_duties' => [],
            'roles' => [$superRoleId],
        ])
        ->assertSessionMissing('access_change_warning');

    // The duty change was committed.
    expect($superAdmin->fresh()->current_duties()->count())->toBe(0)
        ->and($superAdmin->fresh()->isSuperAdmin())->toBeTrue();
});
