<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $role = new Role();

        $role->name = 'Student Representative';
        $role->guard_name = 'web';

        $role->save();

        $role->syncPermissions([
            'institutions.read.own',
            'meetings.create.padalinys',
            'meetings.read.own',
            'meetings.update.own',
            'meetings.delete.own',
            'agendaItems.create.padalinys',
            'agendaItems.read.own',
            'agendaItems.update.own',
            'agendaItems.delete.own',
            'doings.create.padalinys',
            'doings.read.own',
            'doings.update.own',
            'doings.delete.own',
            'comments.create.own',
            'comments.read.own',
            'comments.update.own',
            'sharepointFiles.create.padalinys',
            'sharepointFiles.read.own',
            'sharepointFiles.update.own',
            'tasks.create.padalinys',
            'tasks.read.own',
            'tasks.update.own',
        ]);
    }
}
