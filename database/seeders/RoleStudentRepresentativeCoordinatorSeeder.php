<?php

namespace Database\Seeders;

use App\Models\DutyType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleStudentRepresentativeCoordinatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::firstOrCreate([
            'name' => 'Studentų atstovų koordinatorius',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'users.create.padalinys',
            'users.read.padalinys',
            'users.update.padalinys',
            'users.delete.padalinys',
            'institutions.create.padalinys',
            'institutions.read.padalinys',
            'institutions.update.padalinys',
            'meetings.create.padalinys',
            'meetings.read.padalinys',
            'meetings.update.padalinys',
            'meetings.delete.own',
            'agendaItems.create.padalinys',
            'agendaItems.read.padalinys',
            'agendaItems.update.padalinys',
            'agendaItems.delete.padalinys',
            'duties.create.padalinys',
            'duties.read.padalinys',
            'duties.update.padalinys',
            'duties.delete.padalinys',
            'tasks.create.padalinys',
            'tasks.read.own',
            'tasks.update.own',
            'problems.create.padalinys',
            'problems.update.padalinys',
            'problems.delete.padalinys',
        ]);

        $role->attachable_types()->attach(DutyType::query()->where('slug', 'studentu-atstovai')->firstOrFail());
    }
}
