<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Type;
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
            'problems.read.*',
            'problems.update.padalinys',
        ]);

        // Through Type::roles() so RoleTypeObserver also hands the role to the existing coordinator duties.
        Type::query()->where('slug', 'koordinatoriai')->firstOrFail()
            ->roles()->syncWithoutDetaching([$role->id]);
    }
}
