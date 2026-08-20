<?php

use App\Models\ResourceCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Resource Manager');

    $this->plainUser = makeUser($this->tenant);

    $this->category = ResourceCategory::factory()->create();
});

/**
 * Resource categories are not in ModelEnum, so `resourceCategories.*` permissions are never
 * seeded. ResourceCategoryPolicy maps each ability onto the matching `resources.*` permission
 * instead — previously the controller gated edit, update *and destroy* on `resources.create`.
 */
describe('authorization', function (): void {
    test('a resource manager can list, edit and delete categories', function (): void {
        asUser($this->resourceManager)->get(route('resourceCategories.index'))->assertOk();
        asUser($this->resourceManager)->get(route('resourceCategories.edit', $this->category))->assertOk();

        asUser($this->resourceManager)
            ->delete(route('resourceCategories.destroy', $this->category))
            ->assertRedirect(route('resourceCategories.index'));

        expect(ResourceCategory::query()->whereKey($this->category->id)->exists())->toBeFalse();
    });

    test('a user without resource permissions cannot reach any category action', function (): void {
        asUser($this->plainUser)->get(route('resourceCategories.index'))->assertStatus(403);
        asUser($this->plainUser)->get(route('resourceCategories.create'))->assertStatus(403);
        asUser($this->plainUser)->get(route('resourceCategories.edit', $this->category))->assertStatus(403);
        asUser($this->plainUser)->delete(route('resourceCategories.destroy', $this->category))->assertStatus(403);

        expect(ResourceCategory::query()->whereKey($this->category->id)->exists())->toBeTrue();
    });

    test('deleting is gated by the delete ability, not create', function (): void {
        // Strip only the delete permission — under the old code this user could still destroy
        // categories, because destroy() asked for `resources.create`.
        $role = $this->resourceManager->duties()->first()->roles()->first();
        $role->revokePermissionTo('resources.delete.padalinys');

        asUser($this->resourceManager)
            ->delete(route('resourceCategories.destroy', $this->category))
            ->assertStatus(403);

        expect(ResourceCategory::query()->whereKey($this->category->id)->exists())->toBeTrue();
    });
});
