<?php

use App\Models\Permission;
use Database\Seeders\ModelPermissionSeeder;
use PHPUnit\Framework\Attributes\CoversClass;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('permission seeder doesnt remove existing permissions', function () {
    // assert permission table is created and not empty
    $this->assertDatabaseHas('permissions', [
        'name' => 'permissions.update.padalinys',
        'guard_name' => 'web',
    ]);

    // get this permission
    $permission = Permission::where('name', 'permissions.update.padalinys')->first();

    // assert permission is class of Permission
    expect($permission)->toBeInstanceOf(Permission::class);

    // run db:seed with model permission seeder
    $seeder = new ModelPermissionSeeder();
    $seeder->run();

    // check if permissions are the same
    expect(Permission::where('name', 'permissions.update.padalinys')->first()->id)->toEqual($permission->id);
});