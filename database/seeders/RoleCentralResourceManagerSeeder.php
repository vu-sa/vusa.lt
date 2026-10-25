<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The central office's resource administrator: the padalinys role's abilities in every padalinys.
 * Notification recipients are selected separately by the reservation-handling purpose.
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
            'resources.update.*',
            'resources.delete.*',
            'reservations.read.*',
            'reservations.update.*',
            'reservations.delete.*',
        ]);
    }
}
