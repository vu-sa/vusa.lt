<?php

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Navigation;
use App\Models\QuickLink;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\StudySet;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

pest()->use(RefreshDatabase::class);

/**
 * @return array<string, array<string, mixed>>
 */
function softDeletableResourceCases(): array
{
    return [
        'banner' => [
            'model' => Banner::class,
            'table' => 'banners',
            'route' => 'banners',
            'permission' => 'banners',
            'scope' => 'padalinys',
            'prop' => 'banners.data',
            'create' => fn (Tenant $tenant): Model => Banner::factory()->for($tenant)->create(),
        ],
        'calendar' => [
            'model' => Calendar::class,
            'table' => 'calendar',
            'route' => 'calendar',
            'permission' => 'calendars',
            'scope' => 'padalinys',
            'prop' => 'calendar.data',
            'create' => fn (Tenant $tenant): Model => Calendar::factory()->for($tenant)->create(),
        ],
        'category' => [
            'model' => Category::class,
            'table' => 'categories',
            'route' => 'categories',
            'permission' => 'categories',
            'scope' => '*',
            'prop' => 'categories.data',
            'index_as_super_admin' => true,
            'create' => fn (Tenant $tenant): Model => Category::factory()->create(),
        ],
        'quick link' => [
            'model' => QuickLink::class,
            'table' => 'quick_links',
            'route' => 'quickLinks',
            'permission' => 'quickLinks',
            'scope' => 'padalinys',
            'prop' => 'quickLinks',
            'create' => fn (Tenant $tenant): Model => QuickLink::factory()->for($tenant)->create(['lang' => 'lt']),
        ],
        'navigation' => [
            'model' => Navigation::class,
            'table' => 'navigation',
            'route' => 'navigation',
            'permission' => 'navigations',
            'scope' => '*',
            'prop' => 'navigation',
            'create' => fn (Tenant $tenant): Model => Navigation::factory()->create([
                'lang' => app()->getLocale(),
                'parent_id' => 0,
                'order' => Navigation::withTrashed()->max('order') + 1,
            ]),
        ],
        'tag' => [
            'model' => Tag::class,
            'table' => 'tags',
            'route' => 'tags',
            'permission' => 'tags',
            'scope' => '*',
            'prop' => 'tags.data',
            'create' => fn (Tenant $tenant): Model => Tag::factory()->create(),
        ],
        'study program' => [
            'model' => StudyProgram::class,
            'table' => 'study_programs',
            'route' => 'studyPrograms',
            'permission' => 'studyPrograms',
            'scope' => 'padalinys',
            'prop' => 'studyPrograms.data',
            'create' => fn (Tenant $tenant): Model => StudyProgram::factory()->forTenant($tenant)->create(),
        ],
        'study set' => [
            'model' => StudySet::class,
            'table' => 'study_sets',
            'route' => 'studySets',
            'permission' => 'studySets',
            'scope' => 'padalinys',
            'prop' => 'studySets.data',
            'create' => fn (Tenant $tenant): Model => StudySet::factory()->for($tenant)->create(),
        ],
    ];
}

dataset('soft deletable admin resources', array_map(
    fn (array $resource): array => [$resource],
    softDeletableResourceCases(),
));

/**
 * @param  array<string, mixed>  $resource
 * @return array{0: User, 1: Role}
 */
function makeSoftDeleteResourceAdmin(Tenant $tenant, array $resource): array
{
    $role = Role::updateOrCreate([
        'name' => "Soft Delete {$resource['permission']} {$resource['scope']}",
        'guard_name' => 'web',
    ]);

    $role->syncPermissions(collect(['read', 'create', 'update', 'delete'])
        ->map(fn (string $action): string => "{$resource['permission']}.{$action}.{$resource['scope']}")
        ->all());

    forgetPermissionCaches();

    $user = makeUser($tenant);
    $user->duties()->first()->assignRole($role);

    forgetPermissionCaches();

    return [$user, $role];
}

function forgetPermissionCaches(): void
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    cache()->flush();
}

/**
 * @param  array<string, mixed>  $resource
 */
function createSoftDeleteResource(array $resource, Tenant $tenant): Model
{
    return $resource['create']($tenant);
}

/**
 * @param  array<string, mixed>  $resource
 * @param  array<string, mixed>  $parameters
 */
function softDeleteIndexRoute(array $resource, Tenant $tenant, array $parameters = []): string
{
    if ($resource['route'] === 'quickLinks') {
        $parameters = [
            'tenant' => $tenant->id,
            'lang' => 'lt',
            ...$parameters,
        ];
    }

    return route("{$resource['route']}.index", $parameters);
}

/**
 * @return Collection<int, int|string>
 */
function collectSoftDeleteResourceIds(mixed $payload): Collection
{
    if ($payload instanceof Collection) {
        $payload = $payload->all();
    }

    if (! is_array($payload)) {
        return collect();
    }

    if (array_key_exists('id', $payload)) {
        return collect([$payload['id']])
            ->filter(fn (mixed $id): bool => is_int($id) || is_string($id))
            ->values();
    }

    $ids = collect();

    foreach ($payload as $value) {
        $ids = $ids->merge(collectSoftDeleteResourceIds($value));
    }

    return $ids->values();
}

/**
 * @param  array<string, mixed>  $resource
 */
function grantSoftDeleteResourcePermission(Role $role, array $resource): void
{
    $role->givePermissionTo("{$resource['permission']}.forceDelete.{$resource['scope']}");
    forgetPermissionCaches();
}

/**
 * @param  array<string, mixed>  $resource
 */
function softDeleteIndexAdmin(Tenant $tenant, array $resource): User
{
    if (($resource['index_as_super_admin'] ?? false) === true) {
        return makeAdminUser($tenant);
    }

    return makeSoftDeleteResourceAdmin($tenant, $resource)[0];
}

describe('soft deletable admin resources', function (): void {
    test('destroy soft-deletes instead of removing the row', function (array $resource): void {
        $tenant = Tenant::query()->first();
        [$admin] = makeSoftDeleteResourceAdmin($tenant, $resource);
        $record = createSoftDeleteResource($resource, $tenant);

        asUser($admin)
            ->delete(route("{$resource['route']}.destroy", $record))
            ->assertRedirect();

        $this->assertSoftDeleted($resource['table'], ['id' => $record->getKey()]);
        expect($resource['model']::withTrashed()->find($record->getKey()))->not->toBeNull();
    })->with('soft deletable admin resources');

    test('default index hides soft-deleted records', function (array $resource): void {
        $tenant = Tenant::query()->first();
        $admin = softDeleteIndexAdmin($tenant, $resource);
        $live = createSoftDeleteResource($resource, $tenant);
        $trashed = createSoftDeleteResource($resource, $tenant);
        $trashed->delete();

        $response = asUser($admin)->get(softDeleteIndexRoute($resource, $tenant));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('showDeleted', false));

        $ids = collectSoftDeleteResourceIds(data_get($response->viewData('page')['props'], $resource['prop']));

        expect($ids->contains($live->getKey()))->toBeTrue()
            ->and($ids->contains($trashed->getKey()))->toBeFalse();
    })->with('soft deletable admin resources');

    test('trashed index returns only soft-deleted records', function (array $resource): void {
        $tenant = Tenant::query()->first();
        $admin = softDeleteIndexAdmin($tenant, $resource);
        $live = createSoftDeleteResource($resource, $tenant);
        $trashed = createSoftDeleteResource($resource, $tenant);
        $trashed->delete();

        $response = asUser($admin)->get(softDeleteIndexRoute($resource, $tenant, ['showDeleted' => 'true']));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('showDeleted', true));

        $ids = collectSoftDeleteResourceIds(data_get($response->viewData('page')['props'], $resource['prop']));

        expect($ids->contains($trashed->getKey()))->toBeTrue()
            ->and($ids->contains($live->getKey()))->toBeFalse();
    })->with('soft deletable admin resources');

    test('index exposes deleted count', function (array $resource): void {
        $tenant = Tenant::query()->first();
        $admin = softDeleteIndexAdmin($tenant, $resource);
        $trashed = createSoftDeleteResource($resource, $tenant);
        $trashed->delete();

        asUser($admin)
            ->get(softDeleteIndexRoute($resource, $tenant))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('deletedCount', 1));
    })->with('soft deletable admin resources');

    test('delete permission restores a soft-deleted record', function (array $resource): void {
        $tenant = Tenant::query()->first();
        [$admin] = makeSoftDeleteResourceAdmin($tenant, $resource);
        $trashed = createSoftDeleteResource($resource, $tenant);
        $trashed->delete();

        asUser($admin)
            ->patch(route("{$resource['route']}.restore", $trashed->getKey()))
            ->assertRedirect();

        $this->assertNotSoftDeleted($resource['table'], ['id' => $trashed->getKey()]);
    })->with('soft deletable admin resources');

    test('delete permission alone cannot permanently delete', function (array $resource): void {
        $tenant = Tenant::query()->first();
        [$admin] = makeSoftDeleteResourceAdmin($tenant, $resource);
        $trashed = createSoftDeleteResource($resource, $tenant);
        $trashed->delete();

        asUser($admin)
            ->delete(route("{$resource['route']}.forceDelete", $trashed->getKey()))
            ->assertForbidden();

        expect($resource['model']::withTrashed()->find($trashed->getKey()))->not->toBeNull();
    })->with('soft deletable admin resources');

    test('forceDelete permission permanently deletes trashed records', function (array $resource): void {
        $tenant = Tenant::query()->first();
        [$admin, $role] = makeSoftDeleteResourceAdmin($tenant, $resource);
        $trashed = createSoftDeleteResource($resource, $tenant);
        $trashed->delete();
        grantSoftDeleteResourcePermission($role, $resource);

        asUser($admin)
            ->delete(route("{$resource['route']}.forceDelete", $trashed->getKey()))
            ->assertRedirect();

        expect($resource['model']::withTrashed()->find($trashed->getKey()))->toBeNull();
    })->with('soft deletable admin resources');

    test('live records cannot be permanently deleted', function (array $resource): void {
        $tenant = Tenant::query()->first();
        [$admin, $role] = makeSoftDeleteResourceAdmin($tenant, $resource);
        $live = createSoftDeleteResource($resource, $tenant);
        grantSoftDeleteResourcePermission($role, $resource);

        asUser($admin)
            ->delete(route("{$resource['route']}.forceDelete", $live->getKey()))
            ->assertForbidden();

        $this->assertNotSoftDeleted($resource['table'], ['id' => $live->getKey()]);
    })->with('soft deletable admin resources');
});
