<?php

namespace Database\Seeders;

use App\Models\DutyType;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Lets every coordinator register and follow up problems. It is tied to the `koordinatoriai`
 * duty type rather than to each coordinator role, so a new coordinator duty gets it by its type.
 */
class RoleProblemEditorSeeder extends Seeder
{
    public const NAME = 'Problemų redaktorius';

    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => self::NAME,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'problems.create.padalinys',
            'problems.update.padalinys',
        ]);

        // A type grant reaches the existing coordinator duties through the projection.
        DutyType::query()->where('slug', 'koordinatoriai')->firstOrFail()
            ->responsibilityGrants()->firstOrCreate(['role_id' => $role->id, 'scope_type' => null, 'scope_id' => null]);
    }
}
