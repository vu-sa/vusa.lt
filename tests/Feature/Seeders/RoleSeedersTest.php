<?php

use App\Models\DutyType;
use App\Models\Problem;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleProblemEditorSeeder;
use Database\Seeders\RoleStudentRepresentativeSeeder;
use Database\Seeders\TypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * A user whose duty was tagged with the type before the role was linked to it — the case the
 * type → role link previously missed, since only the observer on DutyType::roles() backfills duties.
 */
function userWithDutyTypedBeforeLink(int $typeId, string $roleName): User
{
    $type = DutyType::query()->findOrFail($typeId);
    $type->roles()->detach(Role::findByName($roleName)->id);

    $user = makeUser(Tenant::query()->firstOrFail());
    $user->duties()->first()->types()->attach($type);

    return $user;
}

test('every coordinator duty, including earlier ones, gets the problem editor role', function (): void {
    $coordinator = userWithDutyTypedBeforeLink(TypeSeeder::COORDINATOR_DUTY_TYPE_ID, RoleProblemEditorSeeder::NAME);

    $this->seed(RoleProblemEditorSeeder::class);

    expect($coordinator->duties()->first()->hasRole(RoleProblemEditorSeeder::NAME))->toBeTrue()
        ->and($coordinator->fresh()->can('create', Problem::class))->toBeTrue();
});

test('every student representative duty, including earlier ones, gets the representative role', function (): void {
    $representative = userWithDutyTypedBeforeLink(TypeSeeder::STUDENT_REPRESENTATIVE_DUTY_TYPE_ID, 'Studentų atstovas');

    $this->seed(RoleStudentRepresentativeSeeder::class);

    expect($representative->duties()->first()->hasRole('Studentų atstovas'))->toBeTrue();
});
