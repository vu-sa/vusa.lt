<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Mirrors the production role of the same name: the student representative coordinator's
 * duties, but across every padalinys.
 */
class RoleCentralStudentRepresentativeCoordinatorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'Centrinio biuro studentų atstovų koordinatorius',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            'users.create.*',
            'users.read.*',
            'users.update.*',
            'users.delete.*',
            'institutions.create.*',
            'institutions.read.*',
            'institutions.update.*',
            'institutions.delete.*',
            'duties.create.*',
            'duties.read.*',
            'duties.update.*',
            'duties.delete.*',
            'meetings.create.*',
            'meetings.read.*',
            'meetings.update.*',
            'meetings.delete.*',
            'agendaItems.create.*',
            'agendaItems.read.*',
            'agendaItems.update.*',
            'agendaItems.delete.*',
            'sharepointFiles.create.*',
            'sharepointFiles.read.*',
            'sharepointFiles.update.*',
            'sharepointFiles.delete.*',
            'comments.delete.padalinys',
            'tasks.create.*',
            'tasks.read.*',
            'tasks.update.*',
            'tasks.delete.*',
            'problems.create.*',
            'problems.update.*',
            'problems.delete.*',
        ]);
    }
}
