<?php

namespace Tests\Feature\Seeders;

use App\Models\Permission;
use Database\Seeders\ModelPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

// TODO: for some reason this doesn't add to test coverage
#[CoversClass(ModelPermissionSeeder::class)]
class ModelPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_doesnt_remove_existing_permissions()
    {
        // assert permission table is created and not empty
        $this->assertDatabaseHas('permissions', [
            'name' => 'permissions.update.padalinys',
            'guard_name' => 'web',
        ]);

        // get this permission
        $permission = Permission::where('name', 'permissions.update.padalinys')->first();

        // assert permission is class of Permission
        $this->assertInstanceOf(Permission::class, $permission);

        // run db:seed with model permission seeder
        $seeder = new ModelPermissionSeeder();
        $seeder->run();

        // check if permissions are the same
        $this->assertEquals($permission->id, Permission::where('name', 'permissions.update.padalinys')->first()->id);
    }
}
