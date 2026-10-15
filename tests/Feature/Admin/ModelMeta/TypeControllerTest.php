<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = makeUser(Tenant::query()->first());
    $this->admin = makeAdminUser(Tenant::query()->first());
});

test('directory hides every dictionary the user cannot read', function (): void {
    asUser($this->user)->get(route('types.index'))->assertForbidden();
    $this->user->givePermissionTo('institutionTypes.read.*');
    app(ModelAuthorizer::class)->resetCache($this->user);
    asUser($this->user)->get(route('types.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/ModelMeta/IndexTypes')
            ->has('destinations', 1)->where('destinations.0.key', 'instituciju_tipai'));
});

test('administrator directory links to all five collections', function (): void {
    asUser($this->admin)->get(route('types.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('destinations', 5));
});

test('ordinary users cannot create or assign dictionary entries', function (string $resource, string $class): void {
    $type = $class::factory()->create();
    asUser($this->user)->post(route($resource.'.store'), ['title' => ['lt' => 'Tipas', 'en' => 'Type']])->assertForbidden();
    asUser($this->user)->put(route($resource.'.models.sync', $type), ['models' => []])->assertForbidden();
})->with([
    ['institutionTypes', InstitutionType::class],
    ['dutyTypes', DutyType::class],
]);

test('each collection creates a translated concrete type', function (string $resource, string $class): void {
    asUser($this->admin)->post(route($resource.'.store'), [
        'title' => ['lt' => 'Naujas tipas', 'en' => 'New type'], 'slug' => 'new-type',
    ])->assertRedirect();
    expect($class::where('slug', 'new-type')->firstOrFail())->toHaveTranslations('title');
})->with([
    ['institutionTypes', InstitutionType::class],
    ['dutyTypes', DutyType::class],
]);

test('parents must belong to the same dictionary and cannot form a cycle', function (): void {
    $root = InstitutionType::factory()->create();
    $child = InstitutionType::factory()->create(['parent_id' => $root->id]);
    asUser($this->admin)->patch(route('institutionTypes.update', $root), [
        'title' => ['lt' => 'Tipas', 'en' => 'Type'], 'parent_id' => $child->id,
    ])->assertSessionHasErrors('parent_id');
    $dutyType = DutyType::factory()->create(['id' => 10000]);
    asUser($this->admin)->patch(route('institutionTypes.update', $root), [
        'title' => ['lt' => 'Tipas', 'en' => 'Type'], 'parent_id' => $dutyType->id,
    ])->assertSessionHasErrors('parent_id');
});

test('assignment rejects owners from the other domain and supports clearing', function (): void {
    $type = InstitutionType::factory()->create();
    $institution = Institution::factory()->create();
    $type->institutions()->attach($institution);
    asUser($this->admin)->put(route('institutionTypes.models.sync', $type), [
        'models' => [Duty::factory()->create()->id],
    ])->assertSessionHasErrors('models.0');
    asUser($this->admin)->put(route('institutionTypes.models.sync', $type), ['models' => []])->assertRedirect();
    expect($type->institutions()->exists())->toBeFalse();
});

test('role links backfill duties and newly assigned duties inherit roles', function (): void {
    $type = DutyType::factory()->create();
    $duty = Duty::factory()->create();
    $type->duties()->attach($duty);
    $role = Role::query()->first();
    $type->roles()->attach($role);
    expect($duty->fresh()->hasRole($role))->toBeTrue();
    $other = Duty::factory()->create();
    $other->types()->attach($type);
    expect($other->fresh()->hasRole($role))->toBeTrue();
    $type->roles()->detach($role);
    expect($duty->fresh()->hasRole($role))->toBeFalse()->and($other->fresh()->hasRole($role))->toBeFalse();
});

test('record exposes assigned owners and deferred files', function (): void {
    $type = InstitutionType::factory()->create();
    $institution = Institution::factory()->create();
    $type->institutions()->attach($institution);
    asUser($this->admin)->get(route('institutionTypes.show', $type))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/ModelMeta/ShowType')
            ->where('attachedModels.0.id', $institution->id)->missing('files')
            ->loadDeferredProps('files', fn (Assert $deferred) => $deferred->has('files')));
});

test('assigned soft-deleted owners block permanent deletion', function (): void {
    $type = DutyType::factory()->create();
    $duty = Duty::factory()->create();
    $type->duties()->attach($duty);
    $duty->delete();
    expect($type->forceDeleteBlockedReason())->not->toBeNull();
});

test('legacy CRUD and public API routes are retired', function (): void {
    expect(app('router')->has('types.store'))->toBeFalse()
        ->and(app('router')->has('api.v1.types.index'))->toBeFalse();
});

test('cycle validation traverses soft-deleted intermediate types', function (): void {
    $root = InstitutionType::factory()->create();
    $middle = InstitutionType::factory()->create(['parent_id' => $root->id]);
    $leaf = InstitutionType::factory()->create(['parent_id' => $middle->id]);
    $middle->delete();
    asUser($this->admin)->patch(route('institutionTypes.update', $root), [
        'title' => ['lt' => 'Tipas', 'en' => 'Type'], 'parent_id' => $leaf->id,
    ])->assertSessionHasErrors('parent_id');
});

test('assigned types can enter the trash and be restored without losing assignments', function (string $resource, string $class, string $ownerClass, string $relation): void {
    $type = $class::factory()->create();
    $owner = $ownerClass::factory()->create();
    $type->{$relation}()->attach($owner);

    asUser($this->admin)->delete(route($resource.'.destroy', $type))->assertRedirect();
    $this->assertSoftDeleted($type);
    asUser($this->admin)->patch(route($resource.'.restore', $type))->assertRedirect()->assertSessionHas('success');

    $this->assertNotSoftDeleted($type);
    expect($type->fresh()->{$relation}()->whereKey($owner->id)->exists())->toBeTrue();
})->with([
    'institution type' => ['institutionTypes', InstitutionType::class, Institution::class, 'institutions'],
    'duty type' => ['dutyTypes', DutyType::class, Duty::class, 'duties'],
]);

test('permanent deletion refuses an assigned owner even when that owner is trashed', function (string $resource, string $class, string $ownerClass, string $relation): void {
    $type = $class::factory()->create();
    $owner = $ownerClass::factory()->create();
    $type->{$relation}()->attach($owner);
    $owner->delete();
    $type->delete();

    asUser($this->admin)->delete(route($resource.'.forceDelete', $type))->assertRedirect()->assertSessionHas('error');

    $this->assertSoftDeleted($type);
    expect($type->{$relation}()->withTrashed()->whereKey($owner->id)->exists())->toBeTrue();
})->with([
    'institution type' => ['institutionTypes', InstitutionType::class, Institution::class, 'institutions'],
    'duty type' => ['dutyTypes', DutyType::class, Duty::class, 'duties'],
]);

test('permanent deletion refuses child types', function (string $resource, string $class): void {
    $type = $class::factory()->create();
    $child = $class::factory()->create(['parent_id' => $type->id]);
    $type->delete();

    asUser($this->admin)->delete(route($resource.'.forceDelete', $type))->assertRedirect()->assertSessionHas('error');

    $this->assertSoftDeleted($type);
    expect($child->fresh()->parent_id)->toBe($type->id);
})->with([
    'institution type' => ['institutionTypes', InstitutionType::class],
    'duty type' => ['dutyTypes', DutyType::class],
]);

test('permanent deletion refuses a duty type with roles', function (): void {
    $type = DutyType::factory()->create();
    $role = Role::query()->where('name', 'Studentų atstovų koordinatorius')->firstOrFail();
    $type->roles()->attach($role);
    $type->delete();

    asUser($this->admin)->delete(route('dutyTypes.forceDelete', $type))->assertRedirect()->assertSessionHas('error');

    $this->assertSoftDeleted($type);
    expect($type->roles()->whereKey($role->id)->exists())->toBeTrue();
});

test('permanent deletion removes a trashed type without dependencies', function (string $resource, string $class): void {
    $type = $class::factory()->create();
    $type->delete();

    asUser($this->admin)->delete(route($resource.'.forceDelete', $type))->assertRedirect()->assertSessionHas('success');

    $this->assertModelMissing($type);
})->with([
    'institution type' => ['institutionTypes', InstitutionType::class],
    'duty type' => ['dutyTypes', DutyType::class],
]);

test('removing a duty type assignment removes its roles and invalidates warmed access maps', function (): void {
    $type = DutyType::factory()->create();
    $role = Role::query()->where('name', 'Studentų atstovų koordinatorius')->firstOrFail();
    $type->roles()->attach($role);
    $duty = $this->user->duties()->first();
    $duty->types()->attach($type);
    $authorizer = app(ModelAuthorizer::class);
    expect($authorizer->allows($this->user, 'institutions.update.padalinys'))->toBeTrue();
    $navigationKey = HandleInertiaRequests::adminNavigationCacheKey($this->user->id);
    $mapKey = PermissionMapBuilder::INDEX_CACHE_PREFIX.$this->user->id;
    Cache::put($navigationKey, ['stale'], 300);
    Cache::put($mapKey, ['stale'], 300);

    asUser($this->admin)->put(route('dutyTypes.models.sync', $type), ['models' => []])->assertRedirect();

    expect($duty->fresh()->hasRole($role))->toBeFalse()
        ->and(Cache::has('auth:duties:'.$this->user->id))->toBeFalse()
        ->and(Cache::has($navigationKey))->toBeFalse()
        ->and(Cache::has($mapKey))->toBeFalse();
});
