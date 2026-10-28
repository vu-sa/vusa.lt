<?php

use App\Models\Institution;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('guests cannot query record abilities', function (): void {
    $this->getJson(route('api.v1.admin.recordAbilities', ['type' => 'resources', 'ids' => ['missing']]))
        ->assertUnauthorized();
});

test('invalid ability batches return 422', function (array $query): void {
    asUser(User::factory()->create())
        ->getJson(route('api.v1.admin.recordAbilities', $query))
        ->assertUnprocessable();
})->with([
    'unknown type' => [['type' => 'App\\Models\\User', 'ids' => ['missing']]],
    'too many ids' => [['type' => 'resources', 'ids' => range(1, 101)]],
    'nested id' => [['type' => 'resources', 'ids' => [['unexpected' => 'id']]]],
    'missing ids' => [['type' => 'resources']],
]);

test('a tenant resource editor only receives mutation abilities within their tenant', function (): void {
    [$tenant, $otherTenant] = Tenant::query()->take(2)->get();
    $editor = makeTenantUserWithRole('Išteklių administratorius', $tenant);
    $own = Resource::factory()->for($tenant)->create();
    $other = Resource::factory()->for($otherTenant)->create();

    asUser($editor)->getJson(route('api.v1.admin.recordAbilities', [
        'type' => 'resources', 'ids' => [$own->id, $other->id, 'missing'],
    ]))->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath("data.{$own->id}", ['update' => true, 'delete' => true])
        ->assertJsonPath("data.{$other->id}", ['update' => false, 'delete' => false])
        ->assertJsonMissingPath('data.missing');
});

test('a read-only member receives no resource mutation abilities', function (): void {
    $tenant = Tenant::query()->first();
    $resource = Resource::factory()->for($tenant)->create();

    asUser(makeUser($tenant))->getJson(route('api.v1.admin.recordAbilities', [
        'type' => 'resources', 'ids' => [$resource->id],
    ]))->assertOk()->assertJsonPath("data.{$resource->id}", ['update' => false, 'delete' => false]);
});

test('unreadable institutions are omitted from the response', function (): void {
    $institution = Institution::factory()->create(['is_active' => false]);

    asUser(User::factory()->create())->getJson(route('api.v1.admin.recordAbilities', [
        'type' => 'institutions', 'ids' => [$institution->id, 'missing'],
    ]))->assertOk()->assertExactJson(['success' => true, 'data' => []]);
});

test('trashed records are omitted from live abilities', function (): void {
    $tenant = Tenant::query()->first();
    $resource = Resource::factory()->for($tenant)->create();
    $resource->delete();

    asUser(makeTenantUserWithRole('Išteklių administratorius', $tenant))
        ->getJson(route('api.v1.admin.recordAbilities', ['type' => 'resources', 'ids' => [$resource->id]]))
        ->assertOk()->assertJsonMissingPath("data.{$resource->id}");
});
