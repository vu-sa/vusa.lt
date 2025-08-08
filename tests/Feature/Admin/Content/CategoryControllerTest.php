<?php

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);

    $this->category = Category::factory()->create([
        'name' => ['lt' => 'Test kategorija', 'en' => 'Test category'],
        'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
    ]);
});

describe('unauthorized access', function () {
    test('cannot access index page', function () {
        asUser($this->user)
            ->get(route('categories.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)
            ->get(route('categories.create'))
            ->assertStatus(403);
    });

    test('cannot store category', function () {
        $validData = getControllerTestData('Category')['valid'];
        // Categories are global, no tenant_id needed

        asUser($this->user)
            ->post(route('categories.store'), $validData)
            ->assertStatus(403);
    });

    test('cannot access edit page', function () {
        asUser($this->user)
            ->get(route('categories.edit', $this->category))
            ->assertStatus(403);
    });

    test('cannot update category', function () {
        $updateData = getControllerTestData('Category')['valid'];
        // Categories are global, no tenant_id needed

        asUser($this->user)
            ->patch(route('categories.update', $this->category), $updateData)
            ->assertStatus(403);
    });

    test('cannot delete category', function () {
        asUser($this->user)
            ->delete(route('categories.destroy', $this->category))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('can access index page', function () {
        asUser($this->admin)
            ->get(route('categories.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexCategory')
                ->has('categories')
                ->has('filters')
                ->has('sorting')
            );
    });

    test('can access create page', function () {
        asUser($this->admin)
            ->get(route('categories.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreateCategory')
                ->has('tenants')
            );
    });

    test('can store category with valid data', function () {
        $validData = getControllerTestData('Category')['valid'];
        // Categories are global, no tenant_id needed
        $uniqueSuffix = time();
        $validData['name']['lt'] = 'Nauja kategorija '.$uniqueSuffix;
        $validData['name']['en'] = 'New category '.$uniqueSuffix;

        $initialCount = Category::count();

        asUser($this->admin)
            ->post(route('categories.store'), $validData)
            ->assertStatus(302)
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        // Verify a new category was created
        expect(Category::count())->toBe($initialCount + 1);

        // Get the newly created category (it should be different from the beforeEach category)
        $createdCategory = Category::orderBy('id', 'desc')->where('id', '!=', $this->category->id)->first();
        expect($createdCategory)->not->toBeNull();
        expect($createdCategory->getTranslation('name', 'lt'))->toBe($validData['name']['lt']);
        expect($createdCategory->getTranslation('name', 'en'))->toBe($validData['name']['en']);
        expect($createdCategory->getTranslation('description', 'lt'))->toBe($validData['description']['lt']);
        expect($createdCategory->getTranslation('description', 'en'))->toBe($validData['description']['en']);
    });

    test('cannot store category with invalid data', function () {
        $invalidData = getControllerTestData('Category')['invalid'];
        // Categories are global, no tenant_id needed

        asUser($this->admin)
            ->post(route('categories.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(getControllerValidationErrors('Category'));
    });

    test('can access edit page', function () {
        asUser($this->admin)
            ->get(route('categories.edit', $this->category))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/EditCategory')
                ->has('category')
                ->where('category.id', $this->category->id)
                ->has('tenants')
            );
    });

    test('can update category with valid data', function () {
        $updateData = getControllerTestData('Category')['valid'];
        $updateData['name'] = ['lt' => 'Atnaujinta kategorija', 'en' => 'Updated category'];
        // Categories are global, no tenant_id needed

        asUser($this->admin)
            ->patch(route('categories.update', $this->category), $updateData)
            ->assertStatus(302)
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $updatedCategory = $this->category->fresh();
        expect($updatedCategory->getTranslation('name', 'lt'))->toBe('Atnaujinta kategorija');
        expect($updatedCategory->getTranslation('name', 'en'))->toBe('Updated category');
    });

    test('cannot update category with invalid data', function () {
        $invalidData = getControllerTestData('Category')['invalid'];
        // Categories are global, no tenant_id needed

        asUser($this->admin)
            ->patch(route('categories.update', $this->category), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(getControllerValidationErrors('Category'));

        // Original data should remain unchanged
        $unchangedCategory = $this->category->fresh();
        expect($unchangedCategory->getTranslation('name', 'lt'))->toBe('Test kategorija');
    });

    test('can delete category when not in use', function () {
        asUser($this->admin)
            ->delete(route('categories.destroy', $this->category))
            ->assertStatus(302)
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', [
            'id' => $this->category->id,
        ]);
    });
});

describe('filtering and search', function () {
    beforeEach(function () {
        // Create additional categories for testing
        Category::factory()->create([
            'name' => ['lt' => 'Kita kategorija', 'en' => 'Another category'],
            'description' => ['lt' => 'Kitas aprašymas', 'en' => 'Another description'],
        ]);
    });

    test('can filter categories by search term', function () {
        asUser($this->admin)
            ->get(route('categories.index', ['search' => 'Test']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexCategory')
                ->has('categories.data')
                ->where('categories.data', function ($data) {
                    return collect($data)->contains(function ($category) {
                        return str_contains($category['name'], 'Test');
                    });
                })
            );
    });

    test('pagination works correctly', function () {
        // Create more categories to test pagination
        Category::factory()->count(25)->create();

        asUser($this->admin)
            ->get(route('categories.index', ['per_page' => 10]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexCategory')
                ->has('categories')
                ->etc()
            );
    });
});

describe('edge cases and business logic', function () {
    test('category creation allows duplicate names globally', function () {
        // Since we removed uniqueness constraints for translatable fields due to SQLite limitations,
        // this test now verifies that duplicate names are allowed
        $duplicateData = getControllerTestData('Category')['valid'];
        $duplicateData['name'] = $this->category->getTranslations('name'); // Same name as existing category
        // Categories are global, no tenant_id needed

        asUser($this->admin)
            ->post(route('categories.store'), $duplicateData)
            ->assertStatus(302)
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');
    });

    test('category handles special characters in name', function () {
        $specialCharsData = getControllerTestData('Category')['valid'];
        $uniqueSuffix = time();
        $specialCharsData['name'] = [
            'lt' => "Kategorija su šiaudiniais žodžiais {$uniqueSuffix}",
            'en' => "Category with special chars & symbols {$uniqueSuffix}",
        ];
        // Categories are global, no tenant_id needed

        asUser($this->admin)
            ->post(route('categories.store'), $specialCharsData)
            ->assertStatus(302)
            ->assertRedirect(route('categories.index'));

        // Check if category was created with special characters (get the most recently created, excluding beforeEach category)
        $createdCategory = Category::orderBy('id', 'desc')->where('id', '!=', $this->category->id)->first();
        expect($createdCategory)->not->toBeNull();
        expect($createdCategory->getTranslation('name', 'lt'))->toBe("Kategorija su šiaudiniais žodžiais {$uniqueSuffix}");
        expect($createdCategory->getTranslation('name', 'en'))->toBe("Category with special chars & symbols {$uniqueSuffix}");
    });
});

describe('global access', function () {
    beforeEach(function () {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherCategory = Category::factory()->create();
        $this->otherAdmin = makeTenantUserWithRole('Global Communication Coordinator', $this->otherTenant);
    });

    test('user can see all categories since they are global', function () {
        // Create additional categories
        Category::factory()->count(3)->create();

        asUser($this->admin)
            ->get(route('categories.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexCategory')
                ->has('categories.data')
            );
    });

    test('can access any category since they are global', function () {
        asUser($this->admin)
            ->get(route('categories.edit', $this->otherCategory))
            ->assertStatus(200);
    });

    test('can update any category since they are global', function () {
        $updateData = getControllerTestData('Category')['valid'];
        $updateData['name'] = ['lt' => 'Atnaujinta kita kategorija', 'en' => 'Updated other category'];

        asUser($this->admin)
            ->patch(route('categories.update', $this->otherCategory), $updateData)
            ->assertStatus(302)
            ->assertRedirect(route('categories.index'));

        $updatedCategory = $this->otherCategory->fresh();
        expect($updatedCategory->getTranslation('name', 'lt'))->toBe('Atnaujinta kita kategorija');
        expect($updatedCategory->getTranslation('name', 'en'))->toBe('Updated other category');
    });
});
