<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleResourceManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = new Role;

        $role->name = 'Resource Manager';
        $role->guard_name = 'web';

        $role->save();

        $role->syncPermissions([
            'resources.create.padalinys',
            'resources.read.*',
            'resources.update.padalinys',
            'resources.delete.padalinys',
            'reservations.create.padalinys',
            'reservations.read.*',
            'reservations.update.padalinys',
            'reservations.delete.padalinys',
        ]);
    }
}
