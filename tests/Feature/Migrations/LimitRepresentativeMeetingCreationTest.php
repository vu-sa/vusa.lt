<?php

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function limitRepresentativeMeetingCreationMigration(): object
{
    return require base_path('database/migrations/2026_09_28_120000_limit_representative_meeting_creation_to_own_institutions.php');
}

beforeEach(function (): void {
    // The production role before the migration: tenant-wide meeting creation.
    $this->role = Role::findByName('Studentų atstovas');
    $this->role->revokePermissionTo('meetings.create.own');
    $this->role->givePermissionTo('meetings.create.padalinys');
});

test('narrows meeting creation to the representative\'s own institutions', function (): void {
    limitRepresentativeMeetingCreationMigration()->up();

    $role = $this->role->fresh();

    expect($role->hasPermissionTo('meetings.create.own'))->toBeTrue()
        ->and($role->permissions->contains('name', 'meetings.create.padalinys'))->toBeFalse()
        ->and($role->hasAllPermissions(['meetings.read.own', 'meetings.update.own']))->toBeTrue();
});

test('rolling back restores tenant-wide creation', function (): void {
    $migration = limitRepresentativeMeetingCreationMigration();
    $migration->up();
    $migration->down();

    $role = $this->role->fresh();

    expect($role->hasPermissionTo('meetings.create.padalinys'))->toBeTrue()
        ->and($role->permissions->contains('name', 'meetings.create.own'))->toBeFalse();
});
