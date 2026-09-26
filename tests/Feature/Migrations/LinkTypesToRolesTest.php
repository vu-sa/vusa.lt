<?php

use App\Models\Problem;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Database\Seeders\RoleResourceManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function linkTypesToRolesMigration(): object
{
    return require base_path('database/migrations/2026_09_26_165608_link_representative_and_coordinator_types_to_roles.php');
}

/** Production state: the roles and typed duties exist, but no type is linked to a role. */
function typedDutyUser(string $typeSlug): User
{
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->duties()->first()->types()->attach(Type::query()->where('slug', $typeSlug)->firstOrFail());

    return $user;
}

beforeEach(function (): void {
    Type::query()->whereIn('slug', ['koordinatoriai', 'studentu-atstovai'])->each(fn (Type $type) => $type->roles()->detach());
    Role::query()->whereIn('name', ['Problemų redaktorius', 'Padalinio puslapių redaktorius'])->delete();
    Role::create(['name' => 'Studentų atstovas', 'guard_name' => 'web']);
});

test('hands the representative role to every duty already typed as a student representative', function (): void {
    $representative = typedDutyUser('studentu-atstovai');

    linkTypesToRolesMigration()->up();

    expect($representative->duties()->first()->hasRole('Studentų atstovas'))->toBeTrue();
});

test('lets every coordinator register problems through the new problem editor role', function (): void {
    $coordinator = typedDutyUser('koordinatoriai');

    linkTypesToRolesMigration()->up();

    expect($coordinator->duties()->first()->hasRole('Problemų redaktorius'))->toBeTrue()
        ->and($coordinator->fresh()->can('create', Problem::class))->toBeTrue();
});

test('creates the page editor role without handing it out when no role holds page editing', function (): void {
    linkTypesToRolesMigration()->up();

    $pageEditor = Role::findByName('Padalinio puslapių redaktorius');

    expect($pageEditor->permissions->pluck('name')->sort()->values()->all())->toBe(['pages.read.padalinys', 'pages.update.padalinys'])
        ->and($pageEditor->duties()->exists())->toBeFalse();
});

test('rolling back takes the roles away from the typed duties again', function (): void {
    $representative = typedDutyUser('studentu-atstovai');
    $coordinator = typedDutyUser('koordinatoriai');
    $migration = linkTypesToRolesMigration();

    $migration->up();
    $migration->down();

    expect($representative->duties()->first()->hasRole('Studentų atstovas'))->toBeFalse()
        ->and(Role::query()->where('name', 'Problemų redaktorius')->exists())->toBeFalse()
        ->and($coordinator->fresh()->can('create', Problem::class))->toBeFalse();
});

describe('splitting page and problem editing out of the resource manager role', function (): void {
    beforeEach(function (): void {
        // The production role: resources plus page and problem editing.
        $this->resourceManager = Role::findByName(RoleResourceManagerSeeder::NAME);
        $this->resourceManager->givePermissionTo(['pages.read.padalinys', 'pages.update.padalinys', 'problems.create.padalinys', 'problems.read.*', 'problems.update.padalinys']);

        $this->chair = makeTenantUserWithRole(RoleResourceManagerSeeder::NAME);
        $this->directHolder = makeUser(Tenant::query()->firstOrFail());
        $this->directHolder->assignRole(RoleResourceManagerSeeder::NAME);
    });

    test('every holder keeps page and problem editing through the new roles', function (): void {
        linkTypesToRolesMigration()->up();

        $duty = $this->chair->duties()->first();

        expect($duty->hasRole('Padalinio puslapių redaktorius'))->toBeTrue()
            ->and($duty->hasRole('Problemų redaktorius'))->toBeTrue()
            ->and($this->directHolder->hasAllRoles(['Padalinio puslapių redaktorius', 'Problemų redaktorius']))->toBeTrue()
            ->and(app(ModelAuthorizer::class)->allows($this->chair->fresh(), 'pages.update.padalinys'))->toBeTrue()
            ->and(app(ModelAuthorizer::class)->allows($this->chair->fresh(), 'problems.create.padalinys'))->toBeTrue();
    });

    test('the resource manager role keeps only resources and reservations', function (): void {
        linkTypesToRolesMigration()->up();

        expect($this->resourceManager->fresh()->permissions->pluck('name')->filter(fn (string $name) => str_starts_with($name, 'pages.') || str_starts_with($name, 'problems.')))->toBeEmpty();
    });

    test('a role holding only part of a set keeps it and is not split', function (): void {
        $this->resourceManager->revokePermissionTo('pages.update.padalinys');

        linkTypesToRolesMigration()->up();

        expect($this->chair->duties()->first()->hasRole('Padalinio puslapių redaktorius'))->toBeFalse()
            ->and($this->resourceManager->fresh()->hasPermissionTo('pages.read.padalinys'))->toBeTrue();
    });

    test('rolling back hands the permissions back to the resource manager role', function (): void {
        $migration = linkTypesToRolesMigration();
        $migration->up();
        $migration->down();

        expect($this->resourceManager->fresh()->hasPermissionTo('pages.update.padalinys'))->toBeTrue()
            ->and($this->resourceManager->fresh()->hasPermissionTo('problems.create.padalinys'))->toBeTrue();
    });
});
