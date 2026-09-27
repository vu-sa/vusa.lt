<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function removeInapplicableForceDeleteMigration(): object
{
    return require base_path('database/migrations/2026_09_28_140000_remove_inapplicable_force_delete_permissions.php');
}

beforeEach(function (): void {
    // Production before the migration: the rows exist and the full administrator holds two.
    foreach (['comments.forceDelete.padalinys', 'comments.forceDelete.*', 'reservations.forceDelete.padalinys', 'reservations.forceDelete.*'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $this->admin = Role::create(['name' => 'Pilnas administratorius', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['comments.forceDelete.*', 'reservations.forceDelete.*', 'news.forceDelete.*']);
});

test('removes permanent deletion where there is no trash to empty, and only there', function (): void {
    removeInapplicableForceDeleteMigration()->up();

    expect(Permission::query()->where('name', 'like', 'comments.forceDelete.%')->orWhere('name', 'like', 'reservations.forceDelete.%')->exists())->toBeFalse()
        ->and($this->admin->fresh()->permissions->pluck('name')->all())->toBe(['news.forceDelete.*']);
});

test('rolling back restores the rows and the administrator\'s grants', function (): void {
    $migration = removeInapplicableForceDeleteMigration();
    $migration->up();
    $migration->down();

    expect($this->admin->fresh()->hasAllPermissions(['comments.forceDelete.*', 'reservations.forceDelete.*']))->toBeTrue()
        ->and(Permission::query()->where('name', 'reservations.forceDelete.padalinys')->exists())->toBeTrue();
});
