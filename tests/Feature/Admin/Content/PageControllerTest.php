<?php

use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->page = Page::factory()->for($this->tenant)->create([
        'title' => 'Test puslapis',
        'permalink' => 'test-page',
        'lang' => 'lt',
    ]);
});

describe('unauthorized access', function () {
    test('cannot access index page', function () {
        asUser($this->user)
            ->get(route('pages.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)
            ->get(route('pages.create'))
            ->assertStatus(403);
    });

    test('cannot store page', function () {
        $validData = getControllerTestData('Page')['valid'];
        $validData['tenant_id'] = $this->tenant->id;

        asUser($this->user)
            ->post(route('pages.store'), $validData)
            ->assertStatus(403);
    });

    test('cannot access edit page', function () {
        asUser($this->user)
            ->get(route('pages.edit', $this->page))
            ->assertStatus(403);
    });

    test('cannot update page', function () {
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->user)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(403);
    });

    test('cannot delete page', function () {
        asUser($this->user)
            ->delete(route('pages.destroy', $this->page))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('can access index page', function () {
        asUser($this->admin)
            ->get(route('pages.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages')
                ->has('pages.data')
            );
    });

    test('can access create page', function () {
        asUser($this->admin)
            ->get(route('pages.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreatePage')
                ->has('tenants')
            );
    });

    test('can store page with valid data', function () {
        $validData = getControllerTestData('Page')['valid'];
        $validData['tenant_id'] = $this->tenant->id;
        $uniqueSuffix = time();
        $validData['permalink'] = 'test-page-'.$uniqueSuffix;

        asUser($this->admin)
            ->post(route('pages.store'), $validData)
            ->assertStatus(302)
            ->assertRedirect(route('pages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'title' => $validData['title'],
            'permalink' => $validData['permalink'],
            'lang' => $validData['lang'],
            'is_active' => $validData['is_active'],
            'tenant_id' => $this->tenant->id,
        ]);
    });

    test('cannot store page with invalid data', function () {
        $invalidData = getControllerTestData('Page')['invalid'];
        $invalidData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(getControllerValidationErrors('Page'));
    });

    test('can access edit page', function () {
        asUser($this->admin)
            ->get(route('pages.edit', $this->page))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/EditPage')
                ->has('page')
                ->where('page.id', $this->page->id)
                ->has('tenants')
            );
    });

    test('can update page with valid data', function () {
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['title'] = 'Atnaujintas puslapis';
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'title' => 'Atnaujintas puslapis',
        ]);
    });

    test('cannot update page with invalid data', function () {
        $invalidData = getControllerTestData('Page')['invalid'];
        $invalidData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->patch(route('pages.update', $this->page), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['title', 'content.parts', 'lang', 'is_active']);

        // Original data should remain unchanged
        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'title' => $this->page->title,
        ]);
    });

    test('can delete page', function () {
        asUser($this->admin)
            ->delete(route('pages.destroy', $this->page))
            ->assertStatus(302)
            ->assertRedirect(route('pages.index'))
            ->assertSessionHas('info');

        $this->assertSoftDeleted('pages', [
            'id' => $this->page->id,
        ]);
    });
});

describe('filtering and search', function () {
    beforeEach(function () {
        // Create additional pages for testing
        Page::factory()->for($this->tenant)->create([
            'title' => 'Another page',
            'permalink' => 'another-page',
            'lang' => 'en',
        ]);
    });

    test('can filter pages by search term', function () {
        asUser($this->admin)
            ->get(route('pages.index', ['search' => 'Test']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages.data')
                ->where('pages.data', function ($data) {
                    return collect($data)->contains(function ($page) {
                        return str_contains($page['title'], 'Test');
                    });
                })
            );
    });

    test('can filter pages by language', function () {
        asUser($this->admin)
            ->get(route('pages.index', ['filters' => json_encode(['lang' => ['en']])]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages.data')
                ->where('pages.data', function ($data) {
                    return collect($data)->every(fn ($page) => $page['lang'] === 'en');
                })
            );
    });
});

describe('edge cases and business logic', function () {
    test('page permalink must be unique within tenant', function () {
        $duplicateData = getControllerTestData('Page')['valid'];
        $duplicateData['permalink'] = $this->page->permalink; // Same permalink
        $duplicateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $duplicateData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['permalink']);
    });

    test('page handles special characters in content', function () {
        $specialCharsData = getControllerTestData('Page')['valid'];
        $specialCharsData['title'] = 'Puslapis su šiaudiniais žodžiais';
        $specialCharsData['content'] = [
            'parts' => [
                [
                    'type' => 'tiptap',
                    'json_content' => [
                        'type' => 'doc',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Turinys su šiaudiniais žodžiais'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $specialCharsData['permalink'] = 'special-chars-page';
        $specialCharsData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $specialCharsData)
            ->assertStatus(302)
            ->assertRedirect(route('pages.index'));

        $this->assertDatabaseHas('pages', [
            'title' => 'Puslapis su šiaudiniais žodžiais',
            'permalink' => 'special-chars-page',
        ]);
    });
});

describe('tenant isolation', function () {
    beforeEach(function () {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherPage = Page::factory()->for($this->otherTenant)->create();
        $this->otherAdmin = makeTenantUserWithRole('Communication Coordinator', $this->otherTenant);
    });

    test('user only sees pages from their tenant', function () {
        asUser($this->admin)
            ->get(route('pages.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages.data')
                ->where('pages.data', function ($data) {
                    return collect($data)->every(fn ($page) => $page['tenant_id'] === $this->tenant->id);
                })
            );
    });

    test('cannot access other tenant page', function () {
        asUser($this->admin)
            ->get(route('pages.edit', $this->otherPage))
            ->assertStatus(403); // Authorization failure - cannot access other tenant's page
    });

    test('cannot update other tenant page', function () {
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->patch(route('pages.update', $this->otherPage), $updateData)
            ->assertStatus(403); // Authorization failure - cannot update other tenant's page
    });
});
