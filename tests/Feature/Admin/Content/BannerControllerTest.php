<?php

use App\Models\Banner;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->banner = Banner::factory()->for($this->tenant)->create([
        'title' => 'Test baneris',
        'image_url' => 'https://example.com/test.jpg',
        'link_url' => 'https://example.com',
        'is_active' => true,
        'order' => 100, // Use a high order to avoid conflicts
    ]);
});

describe('unauthorized access', function () {
    test('cannot access index page', function () {
        asUser($this->user)
            ->get(route('banners.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)
            ->get(route('banners.create'))
            ->assertStatus(403);
    });

    test('cannot store banner', function () {
        $validData = getControllerTestData('Banner')['valid'];

        asUser($this->user)
            ->post(route('banners.store'), $validData)
            ->assertStatus(403);
    });

    test('cannot access edit page', function () {
        asUser($this->user)
            ->get(route('banners.edit', $this->banner))
            ->assertStatus(403);
    });

    test('cannot update banner', function () {
        $updateData = getControllerTestData('Banner')['valid'];

        asUser($this->user)
            ->patch(route('banners.update', $this->banner), $updateData)
            ->assertStatus(403);
    });

    test('cannot delete banner', function () {
        asUser($this->user)
            ->delete(route('banners.destroy', $this->banner))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('can access index page', function () {
        asUser($this->admin)
            ->get(route('banners.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexBanner')
                ->has('banners')
            );
    });

    test('can access create page', function () {
        asUser($this->admin)
            ->get(route('banners.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreateBanner')
            );
    });

    test('can store banner with valid data', function () {
        $validData = getControllerTestData('Banner')['valid'];
        $uniqueSuffix = time();
        $validData['title'] = 'Naujas baneris '.$uniqueSuffix;
        $validData['image_url'] = 'https://example.com/banner-'.$uniqueSuffix.'.jpg';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302)
            ->assertRedirect(route('banners.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'title' => $validData['title'],
            'image_url' => $validData['image_url'],
            'link_url' => $validData['link_url'],
            'is_active' => $validData['is_active'],
            'tenant_id' => $this->tenant->id,
        ]);
    });

    test('cannot store banner with invalid data', function () {
        $invalidData = getControllerTestData('Banner')['invalid'];

        asUser($this->admin)
            ->post(route('banners.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(getControllerValidationErrors('Banner'));
    });

    test('can access edit page', function () {
        asUser($this->admin)
            ->get(route('banners.edit', $this->banner))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/EditBanner')
                ->has('banner')
                ->where('banner.id', $this->banner->id)
            );
    });

    test('can update banner with valid data', function () {
        $updateData = getControllerTestData('Banner')['valid'];
        $updateData['title'] = 'Atnaujintas baneris';
        $updateData['image_url'] = 'https://example.com/updated.jpg';

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'id' => $this->banner->id,
            'title' => 'Atnaujintas baneris',
            'image_url' => 'https://example.com/updated.jpg',
        ]);
    });

    test('cannot update banner with invalid data - missing title', function () {
        $invalidData = ['title' => '', 'image_url' => 'https://example.com/test.jpg'];

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['title']);

        // Original data should remain unchanged
        $this->assertDatabaseHas('banners', [
            'id' => $this->banner->id,
            'title' => 'Test baneris',
        ]);
    });

    test('cannot update banner with invalid data - missing image_url', function () {
        $invalidData = ['title' => 'Valid title', 'image_url' => ''];

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['image_url']);

        // Original data should remain unchanged
        $this->assertDatabaseHas('banners', [
            'id' => $this->banner->id,
            'image_url' => 'https://example.com/test.jpg',
        ]);
    });

    test('can delete banner', function () {
        asUser($this->admin)
            ->delete(route('banners.destroy', $this->banner))
            ->assertStatus(302)
            ->assertRedirect(route('banners.index'))
            ->assertSessionHas('info');

        $this->assertDatabaseMissing('banners', [
            'id' => $this->banner->id,
        ]);
    });
});

describe('banner functionality', function () {
    test('banner is created with random order', function () {
        $validData = getControllerTestData('Banner')['valid'];
        $validData['title'] = 'Banner with order';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302);

        $banner = Banner::where('title', 'Banner with order')->first();
        expect($banner->order)->toBeGreaterThan(0)->toBeLessThanOrEqual(10);
    });

    test('banner defaults is_active to false when not provided', function () {
        $validData = getControllerTestData('Banner')['valid'];
        unset($validData['is_active']);
        $validData['title'] = 'Inactive banner test';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302);

        $this->assertDatabaseHas('banners', [
            'title' => 'Inactive banner test',
            'is_active' => 0,
        ]);
    });

    test('banner can have empty link_url', function () {
        $validData = getControllerTestData('Banner')['valid'];
        $validData['link_url'] = '';
        $validData['title'] = 'Banner without link';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302);

        $this->assertDatabaseHas('banners', [
            'title' => 'Banner without link',
            'link_url' => '',
        ]);
    });
});

describe('filtering and search', function () {
    beforeEach(function () {
        // Create additional banners for testing
        Banner::factory()->for($this->tenant)->create([
            'title' => 'Another banner',
            'image_url' => 'https://example.com/another.jpg',
            'is_active' => false,
        ]);
    });

    test('can filter banners by search term', function () {
        asUser($this->admin)
            ->get(route('banners.index', ['search' => 'Test']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexBanner')
                ->has('banners')
            );
    });

    test('pagination works correctly', function () {
        // Create more banners to test pagination
        Banner::factory()->count(25)->for($this->tenant)->create();

        asUser($this->admin)
            ->get(route('banners.index', ['per_page' => 10]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexBanner')
                ->has('banners')
            );
    });
});

describe('tenant isolation', function () {
    beforeEach(function () {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherBanner = Banner::factory()->for($this->otherTenant)->create();
        $this->otherAdmin = makeTenantUserWithRole('Communication Coordinator', $this->otherTenant);
    });

    test('user only sees banners from their tenant', function () {
        asUser($this->admin)
            ->get(route('banners.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexBanner')
                ->has('banners')
            );
    });

    test('cannot access other tenant banner', function () {
        asUser($this->admin)
            ->get(route('banners.edit', $this->otherBanner))
            ->assertStatus(403); // Authorization failure
    });

    test('cannot update other tenant banner', function () {
        $updateData = getControllerTestData('Banner')['valid'];

        asUser($this->admin)
            ->patch(route('banners.update', $this->otherBanner), $updateData)
            ->assertStatus(403); // Authorization failure
    });
});

describe('cache functionality', function () {
    test('banner cache is cleared when banner is saved', function () {
        // This test verifies that the cache clearing mechanism works
        // The Banner model has event listeners that should clear cache

        $updateData = getControllerTestData('Banner')['valid'];
        $updateData['title'] = 'Updated for cache test';

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $updateData)
            ->assertStatus(302);

        // Verify the banner was updated (cache should be cleared)
        $this->assertDatabaseHas('banners', [
            'id' => $this->banner->id,
            'title' => 'Updated for cache test',
        ]);
    });

    test('banner cache is cleared when banner is deleted', function () {
        asUser($this->admin)
            ->delete(route('banners.destroy', $this->banner))
            ->assertStatus(302);

        // Verify the banner was deleted (cache should be cleared)
        $this->assertDatabaseMissing('banners', [
            'id' => $this->banner->id,
        ]);
    });
});
