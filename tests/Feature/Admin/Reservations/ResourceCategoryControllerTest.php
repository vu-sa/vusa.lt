<?php

use App\Models\ResourceCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Išteklių administratorius');

    $this->plainUser = makeUser($this->tenant);

    $this->category = ResourceCategory::factory()->create();
});

describe('the collection replaces the standalone pages', function (): void {
    test('the index page carries the first page of categories for the collection', function (): void {
        asUser($this->resourceManager)->get(route('resourceCategories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reservations/IndexResourceCategory')
                ->where('abilities.update', true)
                ->where('abilities.delete', true)
                ->where('resourceCategories.data.0.id', $this->category->id)
                ->has('resourceCategories.meta.total'));
    });
});

/**
 * Resource categories are not in ModelEnum, so `resourceCategories.*` permissions are never
 * seeded. ResourceCategoryPolicy maps each ability onto the matching `resources.*` permission
 * instead — previously the controller gated edit, update *and destroy* on `resources.create`.
 */
describe('authorization', function (): void {
    test('a resource manager can list, edit and delete categories', function (): void {
        asUser($this->resourceManager)->get(route('resourceCategories.index'))->assertOk();

        asUser($this->resourceManager)
            ->delete(route('resourceCategories.destroy', $this->category))
            ->assertRedirect(route('resourceCategories.index'));

        expect(ResourceCategory::query()->whereKey($this->category->id)->exists())->toBeFalse();
    });

    test('a user without resource permissions cannot reach any category action', function (): void {
        asUser($this->plainUser)->get(route('resourceCategories.index'))->assertStatus(403);
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

describe('store and update', function (): void {
    test('a resource manager can create a resource category', function (): void {
        asUser($this->resourceManager)
            ->post(route('resourceCategories.store'), [
                'name' => [
                    'lt' => 'Nauja kategorija',
                    'en' => 'New category',
                ],
                'description' => [
                    'lt' => 'Kategorijos aprašymas',
                    'en' => 'Category description',
                ],
                'icon' => 'Speaker',
            ])
            ->assertRedirect(route('resourceCategories.index'));

        expect(ResourceCategory::query()->where('name->lt', 'Nauja kategorija')->exists())->toBeTrue();
    });

    test('a resource manager can update a resource category name and icon', function (): void {
        asUser($this->resourceManager)
            ->patch(route('resourceCategories.update', $this->category), [
                'name' => [
                    'lt' => 'Atnaujinta kategorija',
                    'en' => 'Updated category',
                ],
                'icon' => 'Tv',
            ])
            ->assertRedirect();

        expect($this->category->fresh()->getTranslation('name', 'lt'))->toBe('Atnaujinta kategorija')
            ->and($this->category->fresh()->icon)->toBe('Tv');
    });

    test('a user without resource permissions cannot store or update a category', function (): void {
        asUser($this->plainUser)
            ->post(route('resourceCategories.store'), [
                'name' => ['lt' => 'Bandymas'],
            ])
            ->assertStatus(403);

        asUser($this->plainUser)
            ->patch(route('resourceCategories.update', $this->category), [
                'name' => ['lt' => 'Bandymas'],
            ])
            ->assertStatus(403);
    });
});

