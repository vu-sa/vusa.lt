<?php

use App\Models\ResourceCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $tenant = Tenant::query()->first();
    $this->resourceManager = makeUser($tenant);
    $this->resourceManager->duties()->first()->assignRole('Išteklių administratorius');
    $this->plainUser = makeUser($tenant);
});

test('a resource manager gets the category page in the standard collection shape', function (): void {
    ResourceCategory::factory()->count(3)->create();

    asUser($this->resourceManager)
        ->getJson(route('api.v1.admin.resourceCategories.index', ['per_page' => 2]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('data.per_page', 2)
        ->assertJsonPath('data.current_page', 1)
        ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
});

test('the search term narrows the categories by name', function (): void {
    ResourceCategory::factory()->create(['name' => ['lt' => 'Kėdės', 'en' => 'Chairs']]);
    ResourceCategory::factory()->create(['name' => ['lt' => 'Projektoriai', 'en' => 'Projectors']]);

    asUser($this->resourceManager)
        ->getJson(route('api.v1.admin.resourceCategories.index', ['search' => 'Kėd']))
        ->assertOk()
        ->assertJsonCount(1, 'data.items');
});

test('a user without resource permissions is refused', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.resourceCategories.index'))
        ->assertForbidden();
});
