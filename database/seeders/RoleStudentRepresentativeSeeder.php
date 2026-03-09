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
            'name' => 'Student Representative',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'institutions.read.padalinys',
            'meetings.create.padalinys',
            'meetings.read.own',
            'meetings.update.own',
            'meetings.delete.own',
            'agendaItems.create.padalinys',
            'agendaItems.read.own',
            'agendaItems.update.own',
            'agendaItems.delete.own',
            'comments.create.own',
            'comments.read.own',
            'comments.update.own',
            'sharepointFiles.create.padalinys',
            'sharepointFiles.read.own',
            'sharepointFiles.update.own',
            'tasks.create.padalinys',
            'tasks.read.own',
            'tasks.update.own',
            'problems.create.padalinys',
            'problems.read.padalinys',
            'problems.update.padalinys',
        ]);

        $role->attachable_types()->syncWithoutDetaching([Type::query()->where('slug', 'studentu-atstovai')->firstOrFail()->id]);
    }
}
