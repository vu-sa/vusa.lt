<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function removeBaselinePermissionsMigration(): object
{
    return require base_path('database/migrations/2026_09_28_130000_remove_baseline_permissions_from_roles.php');
}

beforeEach(function (): void {
    // ModelPermissionSeeder no longer creates these; production still has them before the migration.
    foreach (['comments.create.padalinys', 'comments.read.own', 'comments.delete.padalinys', 'duties.read.own', 'problems.read.*', 'resources.read.padalinys'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    // The production representative role: baseline grants plus what it genuinely needs.
    $this->representative = Role::findByName('Studentų atstovas');
    $this->representative->givePermissionTo(['comments.create.padalinys', 'comments.read.own', 'duties.read.own', 'problems.read.*', 'resources.read.padalinys', 'tasks.delete.own']);

    $this->coordinator = Role::findByName('Komunikacijos koordinatorius');
    $this->coordinator->givePermissionTo(['problems.read.*', 'tasks.delete.own', 'comments.delete.padalinys']);
});

test('strips what every member already has, and deleting colleagues\' tasks', function (): void {
    removeBaselinePermissionsMigration()->up();

    $representative = $this->representative->fresh()->permissions->pluck('name');
    $coordinator = $this->coordinator->fresh()->permissions->pluck('name');

    expect($representative->filter(fn (string $name): bool => str_starts_with($name, 'comments.') || in_array($name, ['duties.read.own', 'problems.read.*', 'resources.read.padalinys', 'tasks.delete.own'], true)))->toBeEmpty()
        ->and($representative)->toContain('meetings.create.own', 'problems.create.padalinys')
        ->and($coordinator)->not->toContain('problems.read.*')
        ->and($coordinator)->not->toContain('tasks.delete.own')
        // Deleting other people's comments is moderation, not baseline.
        ->and($coordinator)->toContain('duties.read.padalinys', 'comments.delete.padalinys')
        ->and(Permission::query()->whereIn('name', ['comments.create.padalinys', 'comments.read.own', 'resources.read.padalinys'])->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'tasks.delete.own')->exists())->toBeTrue();
});

test('rolling back gives the production grants back', function (): void {
    $migration = removeBaselinePermissionsMigration();
    $migration->up();
    $migration->down();

    expect($this->representative->fresh()->hasAllPermissions(['comments.create.padalinys', 'duties.read.own', 'problems.read.*', 'resources.read.padalinys', 'tasks.delete.own']))->toBeTrue();
});
