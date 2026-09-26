<?php

use App\Models\Problem;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Database\Seeders\RoleProblemEditorSeeder;
use Database\Seeders\RoleStudentRepresentativeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * A user whose duty was tagged with the type before the role was linked to it — the case the
 * type → role link previously missed, since only the observer on Type::roles() backfills duties.
 */
function userWithDutyTypedBeforeLink(string $typeSlug, string $roleName): User
{
    $type = Type::query()->where('slug', $typeSlug)->firstOrFail();
    $type->roles()->detach(Role::findByName($roleName)->id);

    $user = makeUser(Tenant::query()->firstOrFail());
    $user->duties()->first()->types()->attach($type);

    return $user;
}

test('every coordinator duty, including earlier ones, gets the problem editor role', function (): void {
    $coordinator = userWithDutyTypedBeforeLink('koordinatoriai', RoleProblemEditorSeeder::NAME);

    $this->seed(RoleProblemEditorSeeder::class);

    expect($coordinator->duties()->first()->hasRole(RoleProblemEditorSeeder::NAME))->toBeTrue()
        ->and($coordinator->fresh()->can('create', Problem::class))->toBeTrue();
});

test('every student representative duty, including earlier ones, gets the representative role', function (): void {
    $representative = userWithDutyTypedBeforeLink('studentu-atstovai', 'Student Representative');

    $this->seed(RoleStudentRepresentativeSeeder::class);

    expect($representative->duties()->first()->hasRole('Student Representative'))->toBeTrue();
});
