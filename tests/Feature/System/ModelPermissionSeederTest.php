<?php

use App\Models\Permission;
use Database\Seeders\ModelPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('permission seeder properly manages permissions based on model scope restrictions', function (): void {
    // Test with 'tags' model which only allows '*' scope
    // First, manually create some disallowed permissions that should be deleted

    // These should be deleted (disallowed scopes for tags)
    Permission::create([
        'name' => 'tags.update.own',
        'guard_name' => 'web',
    ]);

    Permission::create([
        'name' => 'tags.create.padalinys',
        'guard_name' => 'web',
    ]);

    // Get existing allowed permission before seeder runs
    $existingAllowedPermission = Permission::where('name', 'tags.update.*')->first();
    $existingAllowedPermissionId = $existingAllowedPermission?->id;

    // Get existing news permission before seeder runs
    $existingNewsPermission = Permission::where('name', 'news.update.padalinys')->first();
    $existingNewsPermissionId = $existingNewsPermission?->id;

    // Run the seeder
    $seeder = new ModelPermissionSeeder;
    $seeder->run();

    // Assert allowed permissions are preserved (if they existed) or created
    expect(Permission::where('name', 'tags.update.*')->first())
        ->toBeInstanceOf(Permission::class);

    if ($existingAllowedPermissionId) {
        expect(Permission::where('name', 'tags.update.*')->first()->id)
            ->toEqual($existingAllowedPermissionId);
    }

    if ($existingNewsPermissionId) {
        expect(Permission::where('name', 'news.update.padalinys')->first()->id)
            ->toEqual($existingNewsPermissionId);
    }

    // Assert disallowed permissions are deleted
    expect(Permission::where('name', 'tags.update.own')->first())->toBeNull();
    expect(Permission::where('name', 'tags.create.padalinys')->first())->toBeNull();

    // Assert all required allowed permissions exist for tags (only '*' scope)
    expect(Permission::where('name', 'tags.create.*')->first())->toBeInstanceOf(Permission::class);
    expect(Permission::where('name', 'tags.read.*')->first())->toBeInstanceOf(Permission::class)
        ->and(Permission::where('name', 'tags.delete.*')->first())->toBeInstanceOf(Permission::class);

    // Assert news model has all scopes created
    expect(Permission::where('name', 'news.create.own')->first())->toBeInstanceOf(Permission::class);
    expect(Permission::where('name', 'news.create.padalinys')->first())->toBeInstanceOf(Permission::class)
        ->and(Permission::where('name', 'news.create.*')->first())->toBeInstanceOf(Permission::class);
});

test('permission seeder handles institutions model with read-only own scope', function (): void {
    // Test with 'institutions' model which only allows 'own' scope for read operations

    // Create permissions that should be deleted (own scope for non-read operations)
    Permission::create([
        'name' => 'institutions.create.own',
        'guard_name' => 'web',
    ]);

    Permission::create([
        'name' => 'institutions.update.own',
        'guard_name' => 'web',
    ]);

    Permission::create([
        'name' => 'institutions.delete.own',
        'guard_name' => 'web',
    ]);

    // Get existing allowed permission before seeder runs (if it exists)
    $existingReadOwnPermission = Permission::where('name', 'institutions.read.own')->first();
    $existingReadOwnPermissionId = $existingReadOwnPermission?->id;

    // Get existing allowed permission before seeder runs
    $existingAllowedPermission = Permission::where('name', 'institutions.update.padalinys')->first();
    $existingAllowedPermissionId = $existingAllowedPermission?->id;

    // Run the seeder
    $seeder = new ModelPermissionSeeder;
    $seeder->run();

    // Assert disallowed "own" permissions for non-read operations are deleted
    expect(Permission::where('name', 'institutions.create.own')->first())->toBeNull();
    expect(Permission::where('name', 'institutions.update.own')->first())->toBeNull()
        ->and(Permission::where('name', 'institutions.delete.own')->first())->toBeNull();

    // Assert allowed "read.own" permission exists and is preserved if it existed before
    expect(Permission::where('name', 'institutions.read.own')->first())
        ->toBeInstanceOf(Permission::class);

    if ($existingReadOwnPermissionId) {
        expect(Permission::where('name', 'institutions.read.own')->first()->id)
            ->toEqual($existingReadOwnPermissionId);
    }

    // Assert allowed permission is preserved (if it existed) or created
    expect(Permission::where('name', 'institutions.update.padalinys')->first())
        ->toBeInstanceOf(Permission::class);

    if ($existingAllowedPermissionId) {
        expect(Permission::where('name', 'institutions.update.padalinys')->first()->id)
            ->toEqual($existingAllowedPermissionId);
    }

    // Assert all padalinys and * scopes are created for all operations
    expect(Permission::where('name', 'institutions.create.padalinys')->first())->toBeInstanceOf(Permission::class);
    expect(Permission::where('name', 'institutions.create.*')->first())->toBeInstanceOf(Permission::class)
        ->and(Permission::where('name', 'institutions.update.padalinys')->first())->toBeInstanceOf(Permission::class)
        ->and(Permission::where('name', 'institutions.update.*')->first())->toBeInstanceOf(Permission::class)
        ->and(Permission::where('name', 'institutions.delete.padalinys')->first())->toBeInstanceOf(Permission::class)
        ->and(Permission::where('name', 'institutions.delete.*')->first())->toBeInstanceOf(Permission::class);

    // Assert read.own exists but other .own permissions don't
    expect(Permission::where('name', 'institutions.read.own')->first())->toBeInstanceOf(Permission::class);
});

test('permission seeder preserves existing allowed permissions without changing IDs', function (): void {
    // This test specifically verifies that existing allowed permissions keep their IDs

    // Get existing permissions before seeder runs
    $existingPermissions = Permission::whereIn('name', [
        'permissions.update.*',  // This should exist and be preserved
        'roles.create.*',        // This should exist and be preserved
    ])->get()->keyBy('name');

    // Run the seeder
    $seeder = new ModelPermissionSeeder;
    $seeder->run();

    // Verify the permissions still exist with the same IDs
    foreach ($existingPermissions as $name => $permission) {
        $updatedPermission = Permission::where('name', $name)->first();
        expect($updatedPermission)->toBeInstanceOf(Permission::class)
            ->and($updatedPermission->id)->toEqual($permission->id);
    }
});

test('permission seeder retires what every member already has', function (): void {
    Permission::findOrCreate('resources.read.padalinys', 'web');
    Permission::findOrCreate('comments.create.own', 'web');

    $this->seed(ModelPermissionSeeder::class);

    $names = Permission::query()->pluck('name');

    expect($names->filter(fn (string $name): bool => preg_match('/^comments\.(create|read|update)\./', $name) === 1
        || $name === 'comments.delete.own'
        || str_starts_with($name, 'resources.read.')
        || str_starts_with($name, 'problems.read.')
        || $name === 'duties.read.own'))->toBeEmpty()
        ->and($names)->toContain('resources.update.padalinys')
        ->and($names)->toContain('duties.read.padalinys')
        ->and($names)->toContain('tasks.delete.own')
        ->and($names)->toContain('comments.delete.padalinys');
});

test('permission seeder offers permanent deletion only where there is a trash to empty', function (): void {
    Permission::findOrCreate('reservations.forceDelete.*', 'web');
    Permission::findOrCreate('comments.forceDelete.*', 'web');

    $this->seed(ModelPermissionSeeder::class);

    $names = Permission::query()->pluck('name');

    expect($names)->not->toContain('reservations.forceDelete.*')
        ->and($names)->not->toContain('comments.forceDelete.*')
        ->and($names)->toContain('news.forceDelete.*')
        ->and($names)->toContain('comments.delete.*');
});
