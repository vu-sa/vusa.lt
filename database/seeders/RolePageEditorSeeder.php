<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Edits the padalinys' existing website pages without creating or deleting them.
 */
class RolePageEditorSeeder extends Seeder
{
    public const NAME = 'Padalinio puslapių redaktorius';

    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => self::NAME,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'pages.read.padalinys',
            'pages.update.padalinys',
        ]);
    }
}
