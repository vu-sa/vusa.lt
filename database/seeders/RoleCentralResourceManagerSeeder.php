<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The central office's resource administrator: the padalinys role's abilities in every padalinys.
 * Its `resources.update.*` counts as resource managership too (GetResourceManagers::permissionNames()).
 */
class RoleCentralResourceManagerSeeder extends Seeder
{
    public const NAME = 'Centrinio biuro išteklių administratorius';

    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => self::NAME,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'resources.create.*',
            'resources.read.*',
            'resources.update.*',
            'resources.delete.*',
            'reservations.create.*',
            'reservations.read.*',
            'reservations.update.*',
            'reservations.delete.*',
        ]);
    }
}
