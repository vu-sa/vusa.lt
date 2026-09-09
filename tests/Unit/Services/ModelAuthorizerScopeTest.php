<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Authorization\PermissionScope;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
    $this->authorizer = app(ModelAuthorizer::class);
});

/**
 * A user holding $permissions through a duty in $tenant.
 */
function actorWithPermissions(Tenant $tenant, array $permissions, string $roleName = 'Scope Test Role'): User
{
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $role->syncPermissions(collect($permissions)->map(
        fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
    )->all());

    $user = makeUser($tenant);
    $user->duties()->first()->assignRole($role);

    return $user;
}

describe('resolution', function (): void {
    test('a super admin is granted every permission across every tenant', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $scope = $this->authorizer->scope($superAdmin, 'news.update.padalinys');

        expect($scope->granted)->toBeTrue()
            ->and($scope->isAllScope)->toBeTrue()
            ->and($scope->tenants)->toHaveCount(Tenant::count());
    });

    test('a duty-granted padalinys permission scopes to that duty tenant only', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);

        $scope = $this->authorizer->scope($user, 'news.update.padalinys');

        expect($scope->granted)->toBeTrue()
            ->and($scope->isAllScope)->toBeFalse()
            ->and($scope->tenantIds()->all())->toBe([$this->tenant->id])
            ->and($scope->duties)->toHaveCount(1);
    });

    test('a duty-granted global permission reaches every tenant', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.*']);

        $scope = $this->authorizer->scope($user, 'news.update.*');

        expect($scope->isAllScope)->toBeTrue()
            ->and($scope->tenants)->toHaveCount(Tenant::count());
    });

    test('only the duties that actually grant the permission are returned', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);

        // A second, permissionless duty in another tenant.
        $user->duties()->attach(
            Duty::factory()->for(Institution::factory()->for($this->otherTenant))->create(),
            ['start_date' => now()->subDay()]
        );

        $scope = $this->authorizer->scope($user->refresh(), 'news.update.padalinys');

        expect($scope->duties)->toHaveCount(1)
            ->and($scope->tenantIds()->all())->toBe([$this->tenant->id]);
    });

    /**
     * The #621 acceptance criterion. getTenants() used to fall back to every tenant the
     * actor held any current duty in whenever no duty granted the permission, handing
     * tenant rights to someone who merely worked there.
     */
    test('an actor without the permission gets no tenants, despite holding a duty', function (): void {
        $user = makeUser($this->tenant);

        $scope = $this->authorizer->scope($user, 'news.update.padalinys');

        expect($scope->granted)->toBeFalse()
            ->and($scope->isAllScope)->toBeFalse()
            ->and($scope->tenants)->toBeEmpty()
            ->and($scope->duties)->toBeEmpty();
    });

    test('an ended duty grants nothing', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);

        $user->duties()->first()->pivot->update(['end_date' => now()->subDay()]);
        $this->authorizer->resetCache($user);

        expect($this->authorizer->allows($user->refresh(), 'news.update.padalinys'))->toBeFalse();
    });
});

describe('purity across permissions', function (): void {
    /**
     * Regression guard for the sticky all-scope flag: checkAllRoleables() reset
     * permissableDuties on every call but never isAllScope, so the first `*`-scoped
     * permission in a request latched it true for every later resolution — and the memo
     * then stored that true. A Resource Manager holding `resources.read.*` gained
     * all-tenant `resources.update.padalinys`; a Global Communication Coordinator holding
     * `tags.update.*` gained all-tenant everything.
     */
    test('an all-scope resolution does not widen a later padalinys resolution', function (): void {
        $user = actorWithPermissions($this->tenant, ['tags.update.*', 'news.update.padalinys']);

        expect($this->authorizer->scope($user, 'tags.update.*')->isAllScope)->toBeTrue();

        $narrow = $this->authorizer->scope($user, 'news.update.padalinys');

        expect($narrow->isAllScope)->toBeFalse()
            ->and($narrow->tenantIds()->all())->toBe([$this->tenant->id]);
    });

    test('a padalinys resolution does not narrow a later all-scope resolution', function (): void {
        $user = actorWithPermissions($this->tenant, ['tags.update.*', 'news.update.padalinys']);

        expect($this->authorizer->scope($user, 'news.update.padalinys')->isAllScope)->toBeFalse()
            ->and($this->authorizer->scope($user, 'tags.update.*')->isAllScope)->toBeTrue();
    });

    test('a denied resolution does not poison a granted one for the same actor', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);

        expect($this->authorizer->scope($user, 'documents.update.padalinys')->tenants)->toBeEmpty()
            ->and($this->authorizer->scope($user, 'news.update.padalinys')->tenantIds()->all())->toBe([$this->tenant->id]);
    });
});

describe('memoization', function (): void {
    test('the same pair returns the same immutable result', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);

        $first = $this->authorizer->scope($user, 'news.update.padalinys');
        $second = $this->authorizer->scope($user, 'news.update.padalinys');

        expect($second)->toBe($first)
            ->and($first)->toBeInstanceOf(PermissionScope::class);
    });

    test('different permissions and different users memoize separately', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);
        $other = makeUser($this->otherTenant);

        expect($this->authorizer->scope($user, 'news.update.padalinys'))
            ->not->toBe($this->authorizer->scope($user, 'news.read.padalinys'))
            ->and($this->authorizer->scope($other, 'news.update.padalinys')->granted)->toBeFalse()
            ->and($this->authorizer->scope($user, 'news.update.padalinys')->granted)->toBeTrue();
    });
});

describe('cache invalidation', function (): void {
    test('resetCache drops the memo and the persisted duty cache', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);

        $first = $this->authorizer->scope($user, 'news.update.padalinys');
        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        $this->authorizer->resetCache($user);

        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse()
            ->and($this->authorizer->scope($user, 'news.update.padalinys'))->not->toBe($first);
    });

    test('resetting one user leaves another users memo intact', function (): void {
        $user = actorWithPermissions($this->tenant, ['news.update.padalinys']);
        $other = actorWithPermissions($this->otherTenant, ['news.update.padalinys'], 'Scope Test Role Other');

        $otherScope = $this->authorizer->scope($other, 'news.update.padalinys');
        $this->authorizer->resetCache($user);

        expect($this->authorizer->scope($other, 'news.update.padalinys'))->toBe($otherScope);
    });

    /**
     * assignRole()/removeRole() on a duty fire Spatie events, not Eloquent model events,
     * so nothing invalidates the duty cache on its own — callers must reset explicitly.
     */
    test('a role change on a duty requires an explicit reset to take effect', function (): void {
        $user = makeUser($this->tenant);
        expect($this->authorizer->allows($user, 'news.update.padalinys'))->toBeFalse();

        $role = Role::firstOrCreate(['name' => 'Late Grant', 'guard_name' => 'web']);
        $role->syncPermissions([Permission::firstOrCreate(['name' => 'news.update.padalinys', 'guard_name' => 'web'])]);
        $user->duties()->first()->assignRole($role);

        expect($this->authorizer->allows($user, 'news.update.padalinys'))->toBeFalse();

        $this->authorizer->resetCache($user);

        expect($this->authorizer->allows($user->refresh(), 'news.update.padalinys'))->toBeTrue();
    });
});
