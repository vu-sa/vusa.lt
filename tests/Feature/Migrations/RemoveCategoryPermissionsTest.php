<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function removeCategoryPermissionsMigration(): object
{
    return require base_path('database/migrations/2026_09_28_150000_remove_category_permissions.php');
}

beforeEach(function (): void {
    // Production before the migration: permissions of a model that no longer exists.
    foreach (['create', 'read', 'update', 'delete', 'forceDelete'] as $ability) {
        Permission::findOrCreate("categories.{$ability}.*", 'web');
    }

    $this->auditor = Role::create(['name' => 'Revizijos komisijos narys', 'guard_name' => 'web']);
    $this->auditor->givePermissionTo(['categories.read.*', 'news.read.padalinys']);
});

test('removes the category permissions and keeps everything else', function (): void {
    removeCategoryPermissionsMigration()->up();

    expect(Permission::query()->where('name', 'like', 'categories.%')->exists())->toBeFalse()
        ->and($this->auditor->fresh()->permissions->pluck('name')->all())->toBe(['news.read.padalinys']);
});

test('rolling back restores them to their holders', function (): void {
    $migration = removeCategoryPermissionsMigration();
    $migration->up();
    $migration->down();

    expect($this->auditor->fresh()->hasAllPermissions(['categories.create.*', 'categories.read.*', 'categories.update.*']))->toBeTrue();
});
