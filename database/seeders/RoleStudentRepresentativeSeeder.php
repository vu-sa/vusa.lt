<?php

namespace Database\Seeders;

use App\Enums\ResponsibilityPurpose;
use App\Models\DutyType;
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
        $role = Role::firstOrCreate([
            'name' => 'Studentų atstovas',
            'guard_name' => 'web',
        ], [
            'title' => ['lt' => 'Studentų atstovavimas', 'en' => 'Student representation'],
            'purpose' => ResponsibilityPurpose::StudentRepresentation,
        ]);

        $role->syncPermissions([
            'meetings.create.own',
            'meetings.update.own',
            'meetings.delete.own',
            'tasks.create.padalinys',
            'problems.create.padalinys',
            'problems.update.padalinys',
        ]);

        $type = DutyType::query()->find(TypeSeeder::STUDENT_REPRESENTATIVE_DUTY_TYPE_ID)
            ?? DutyType::query()->where('title->lt', 'Studentų atstovas')->firstOrFail();

        // Coordinators can attach this type to duties
        $role->attachable_types()->syncWithoutDetaching([$type->id]);

        // A type grant reaches the existing duties of this type through the projection.
        $type->responsibilityGrants()->firstOrCreate(['role_id' => $role->id, 'scope_type' => null, 'scope_id' => null]);
    }
}
