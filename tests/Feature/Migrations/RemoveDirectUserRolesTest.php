<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/** Delete with the migration once it has run on production. */
function removeDirectUserRoles(): object
{
    return require base_path('database/migrations/2026_10_23_181327_remove_direct_user_roles_except_super_admin.php');
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->role = Role::firstOrCreate(['name' => 'Komunikacijos koordinatorius', 'guard_name' => 'web']);
});

test('takes every role but Super Admin off accounts, leaving duties\' roles alone', function (): void {
    $holder = makeUser($this->tenant);
    $holder->assignRole($this->role);
    $superAdmin = makeUser($this->tenant);
    $superAdmin->assignRole(config('permission.super_admin_role_name'));
    $duty = makeUser($this->tenant)->duties()->first();
    $duty->assignRole($this->role);

    removeDirectUserRoles()->up();

    expect($holder->fresh()->roles)->toBeEmpty()
        ->and($superAdmin->fresh()->isSuperAdmin())->toBeTrue()
        ->and($duty->fresh()->hasRole($this->role))->toBeTrue();
});

test('the account no longer reaches the padaliniai of its other duties', function (): void {
    $this->role->givePermissionTo('news.create.padalinys');
    $holder = makeUser($this->tenant);
    $holder->assignRole($this->role);

    expect(app(ModelAuthorizer::class)->allows($holder, 'news.create.padalinys'))->toBeTrue();

    removeDirectUserRoles()->up();

    expect(app(ModelAuthorizer::class)->allows($holder->fresh(), 'news.create.padalinys'))->toBeFalse();
});
