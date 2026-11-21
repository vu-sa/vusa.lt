<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

pest()->use(RefreshDatabase::class);

// Goals keep their own invariants even for super admins: GoalControllerTest covers that exception.
test('a super admin passes every ability without holding the permission', function (string $ability): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(config('permission.super_admin_role_name'));
    $role = Role::query()->where('name', '!=', config('permission.super_admin_role_name'))->firstOrFail();

    expect($superAdmin->getAllPermissions())->toBeEmpty()
        ->and(Gate::forUser($superAdmin)->allows($ability, $role))->toBeTrue()
        ->and(Gate::forUser(makeUser(Tenant::query()->first()))->allows($ability, $role))->toBeFalse();
})->with(['view', 'update', 'delete']);
