<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Type;
use Illuminate\Database\Seeder;

class RoleStudentRepresentativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::firstOrCreate([
            'name' => 'Studentų atstovas',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'institutions.read.own',
            'meetings.create.own',
            'meetings.read.own',
            'meetings.update.own',
            'meetings.delete.own',
            'agendaItems.create.padalinys',
            'agendaItems.read.own',
            'agendaItems.update.own',
            'agendaItems.delete.own',
            'sharepointFiles.create.padalinys',
            'sharepointFiles.read.own',
            'sharepointFiles.update.own',
            'tasks.create.padalinys',
            'tasks.read.own',
            'tasks.update.own',
            'problems.create.padalinys',
            'problems.update.padalinys',
        ]);

        $type = Type::query()->where('slug', 'studentu-atstovai')->firstOrFail();

        // Coordinators can attach this type to duties
        $role->attachable_types()->syncWithoutDetaching([$type->id]);

        // Through Type::roles() so RoleTypeObserver also hands the role to the existing duties of this type.
        $type->roles()->syncWithoutDetaching([$role->id]);
    }
}
