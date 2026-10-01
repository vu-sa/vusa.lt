<?php

use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = makeUser(Tenant::query()->first());
    $this->admin = makeAdminUser(Tenant::query()->first());
});

test('directory hides every dictionary the user cannot read', function (): void {
    asUser($this->user)->get(route('types.index'))->assertForbidden();
    $this->user->givePermissionTo('institutionTypes.read.*');
    app(\App\Services\ModelAuthorizer::class)->resetCache($this->user);
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
