<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Mirrors the production role that padalinio pirmininkai and administratoriai hold. Its
 * `resources.update.padalinys` is what makes someone a resource manager
 * (`permission.resource_managership_indicating_permission`); see docs/rezervacijos/.
 */
class RoleResourceManagerSeeder extends Seeder
{
    public const NAME = 'Išteklių administratorius';

    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => self::NAME,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'resources.create.padalinys',
            'resources.read.*',
            'resources.update.padalinys',
            'resources.delete.padalinys',
            'reservations.create.padalinys',
            'reservations.read.padalinys',
            'reservations.update.padalinys',
            'reservations.delete.padalinys',
        ]);
    }
}
