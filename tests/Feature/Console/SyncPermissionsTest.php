<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('permissions:sync')->assertSuccessful();
});

test('creates what the catalog offers and is a no-op the second time', function (): void {
    Permission::query()->where('name', 'news.read.padalinys')->delete();

    $this->artisan('permissions:sync')->expectsOutputToContain('Created 1 permissions')->assertSuccessful();
    $this->artisan('permissions:sync')->expectsOutputToContain('Created 0 permissions')->expectsOutputToContain('Removed 0 permissions')->assertSuccessful();

    expect(Permission::query()->where('name', 'news.read.padalinys')->exists())->toBeTrue();
});

test('keeps the id and holders of a permission that is still offered', function (): void {
    $permission = Permission::findByName('news.update.padalinys', 'web');
    $role = Role::factory()->create();
    $role->givePermissionTo($permission);

    $this->artisan('permissions:sync')->assertSuccessful();

    expect(Permission::findByName('news.update.padalinys', 'web')->id)->toBe($permission->id)
        ->and($role->fresh()->hasPermissionTo('news.update.padalinys'))->toBeTrue();
});

test('removes a permission the catalog no longer offers, from the roles that held it', function (): void {
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('tasks.update.own', 'web'));

    $this->artisan('permissions:sync')->expectsOutputToContain('tasks.update.own')->assertSuccessful();

    expect(Permission::query()->where('name', 'tasks.update.own')->exists())->toBeFalse()
        ->and($role->fresh()->permissions->pluck('name'))->not->toContain('tasks.update.own');
});

test('--dry-run reports without writing', function (): void {
    Permission::findOrCreate('users.read.own', 'web');
    Permission::query()->where('name', 'news.read.padalinys')->delete();

    $this->artisan('permissions:sync', ['--dry-run' => true])
        ->expectsOutputToContain('Would create 1 permissions')
        ->expectsOutputToContain('users.read.own')
        ->assertSuccessful();

    expect(Permission::query()->where('name', 'users.read.own')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'news.read.padalinys')->exists())->toBeFalse();
});

test('leaves permissions checked by a super-admin-only policy alone', function (): void {
    Permission::findOrCreate('supportRequests.read.*', 'web');

    $this->artisan('permissions:sync')->assertSuccessful();

    expect(Permission::query()->where('name', 'supportRequests.read.*')->exists())->toBeTrue();
});

test('after a sync the table holds exactly the catalog', function (): void {
    expect(Permission::query()->pluck('name')->diff(PermissionCatalog::names())->all())->toBeEmpty();
});
