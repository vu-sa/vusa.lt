<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Type;
use Illuminate\Database\Seeder;

class RoleGlobalCommunicationCoordinatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::firstOrCreate([
            'name' => 'Global Communication Coordinator',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            // Global tag management
            'tags.create.*',
            'tags.read.*',
            'tags.update.*',
            'tags.delete.*',
            // Other global content management permissions can be added here
        ]);

        // This role can be attached to high-level coordination types
        $role->attachable_types()->attach(Type::query()->where('slug', 'pirmininkas')->firstOrFail());
    }
}
