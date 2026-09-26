<?php

use App\Models\Role;
use Database\Seeders\RoleCentralResourceManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function trimCentralOfficeRoleMigration(): object
{
    return require base_path('database/migrations/2026_09_26_173530_trim_central_office_resource_manager_role.php');
}

beforeEach(function (): void {
    // The production role: its resource set plus unrelated global permissions.
    $this->role = Role::findByName(RoleCentralResourceManagerSeeder::NAME);
    $this->role->givePermissionTo(['pages.update.*', 'users.read.*']);
});

test('keeps only the resource and reservation permissions', function (): void {
    trimCentralOfficeRoleMigration()->up();

    expect($this->role->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe([
        'reservations.create.*', 'reservations.delete.*', 'reservations.read.*', 'reservations.update.*',
        'resources.create.*', 'resources.delete.*', 'resources.read.*', 'resources.update.*',
    ]);
});

test('rolling back restores the removed permissions', function (): void {
    $migration = trimCentralOfficeRoleMigration();
    $migration->up();
    $migration->down();

    expect($this->role->fresh()->hasAllPermissions(['pages.update.*', 'users.read.*']))->toBeTrue();
});
