<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Manages the padalinys' documents in the public archive (ViSAK → Dokumentai).
 */
class RoleDocumentManagerSeeder extends Seeder
{
    public const NAME = 'Padalinio dokumentų valdytojas';

    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => self::NAME,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'documents.create.padalinys',
            'documents.read.padalinys',
            'documents.update.padalinys',
            'documents.delete.padalinys',
        ]);
    }
}
