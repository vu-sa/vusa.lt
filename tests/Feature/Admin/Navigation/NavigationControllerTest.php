<?php

use App\Models\Navigation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Global Communication Coordinator', $this->tenant);

    $this->navigation = Navigation::factory()->create([
        'name' => 'Test Navigation',
        'url' => '/test-nav',
        'parent_id' => 0,
        'order' => 1,
        'lang' => 'lt',
    ]);
});

describe('cache functionality', function (): void {
    test('navigation cache is cleared when navigation is saved', function (): void {
        $updateData = getControllerTestData('Navigation')['valid'];
        $updateData['name'] = 'Updated for cache test';

        asUser($this->admin)
            ->patch(route('navigation.update', $this->navigation), $updateData)
            ->assertStatus(302);

        // Verify the navigation was updated (cache should be cleared)
        $this->assertDatabaseHas('navigation', [
            'id' => $this->navigation->id,
            'name' => 'Updated for cache test',
        ]);
    });

    test('navigation cache is cleared when navigation is created', function (): void {
        $validData = getControllerTestData('Navigation')['valid'];
        $validData['name'] = 'Cache test navigation';

        asUser($this->admin)
            ->post(route('navigation.store'), $validData)
            ->assertStatus(302);

        // Verify the navigation was created (cache should be cleared)
        $this->assertDatabaseHas('navigation', [
            'name' => 'Cache test navigation',
        ]);
    });
});

describe('unauthorized access', function (): void {
    test('cannot access index page', function (): void {
        asUser($this->user)
            ->get(route('navigation.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function (): void {
        asUser($this->user)
            ->get(route('navigation.create'))
            ->assertStatus(403);
    });

    test('cannot store navigation', function (): void {
        $validData = getControllerTestData('Navigation')['valid'];

        asUser($this->user)
            ->post(route('navigation.store'), $validData)
            ->assertStatus(403);
    });

    test('cannot access edit page', function (): void {
        asUser($this->user)
            ->get(route('navigation.edit', $this->navigation))
            ->assertStatus(403);
    });

    test('cannot update navigation', function (): void {
        $updateData = getControllerTestData('Navigation')['valid'];

        asUser($this->user)
            ->patch(route('navigation.update', $this->navigation), $updateData)
            ->assertStatus(403);
    });

    test('cannot delete navigation', function (): void {
        asUser($this->user)
            ->delete(route('navigation.destroy', $this->navigation))
            ->assertStatus(403);
    });

    test('cannot update navigation order', function (): void {
        asUser($this->user)
            ->post(route('navigation.updateOrder'), [
                'navigation' => [
                    ['id' => $this->navigation->id],
                ],
            ])
            ->assertStatus(403);
    });
});

describe('tenant isolation', function (): void {
    // Navigation is a globally-scoped model (see NavigationPolicy / HasCommonChecks —
    // it has no `tenant`/`tenants` relation), so it is only ever granted via the
    // blanket `navigation.*.all` permission. There is no per-tenant "own padalinys"
    // scope to isolate — a role limited to a tenant-scoped permission is simply
    // denied outright, which is what this asserts.
    test('a tenant-scoped role without the .all permission cannot manage navigation', function (): void {
        $tenantScopedUser = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

        asUser($tenantScopedUser)
            ->get(route('navigation.index'))
            ->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    test('can access index page', function (): void {
        asUser($this->admin)
            ->get(route('navigation.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Navigation/IndexNavigation')
                ->has('navigation')
                ->where('lang', 'lt')
                ->has('translationSummary')
            );
    });

    test('index serializes is_active as a real boolean, not 0/1', function (): void {
        // reka-ui's Switch computes `checked` via `modelValue === true` (strict
        // equality — see node_modules/reka-ui/dist/Switch/SwitchRoot.js). Without a
        // `boolean` cast on the model, `toArray()`/JSON serialize `is_active` as an
        // int, and every switch in the builder renders as off regardless of the
        // actual value.
        $response = asUser($this->admin)->get(route('navigation.index'));

        $navigation = $response->viewData('page')['props']['navigation'];
        expect($navigation[0]['is_active'])->toBeBool();
    });

    test('index defaults the builder language to the current app locale', function (): void {
        asUser($this->admin)
            ->get(route('navigation.index'))
            ->assertInertia(fn (Assert $page) => $page->where('lang', app()->getLocale()));
    });

    test('?lang=en scopes the tree and deleted count to English regardless of app locale', function (): void {
        Navigation::factory()->create(['lang' => 'en', 'name' => 'English Root', 'parent_id' => 0]);
        $trashedEnglish = Navigation::factory()->create(['lang' => 'en']);
        $trashedEnglish->delete();

        $response = asUser($this->admin)
            ->get(route('navigation.index', ['lang' => 'en']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('lang', 'en')
                ->where('deletedCount', 1)
            );

        $names = collect($response->viewData('page')['props']['navigation'])->pluck('name');
        expect($names)->toContain('English Root')
            ->and($names)->not->toContain('Test Navigation');
    });

    test('an invalid lang value falls back to the app locale', function (): void {
        asUser($this->admin)
            ->get(route('navigation.index', ['lang' => 'fr']))
            ->assertInertia(fn (Assert $page) => $page->where('lang', app()->getLocale()));
    });

    test('can access create page', function (): void {
        asUser($this->admin)
            ->get(route('navigation.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Navigation/CreateNavigation')
                ->has('parent_id')
                ->has('parentElements')
                ->has('categoryOptions')
                ->where('parent_id', 0)
            );
    });

    test('can access create page with parent_id', function (): void {
        asUser($this->admin)
            ->get(route('navigation.create', ['parent_id' => $this->navigation->id]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Navigation/CreateNavigation')
                ->has('parent_id')
                ->has('parentElements')
                ->where('parent_id', (string) $this->navigation->id) // Cast to string since it comes from request
            );
    });

    test('can store navigation with valid data', function (): void {
        $validData = getControllerTestData('Navigation')['valid'];
        $uniqueSuffix = time();
        $validData['name'] = 'New Navigation '.$uniqueSuffix;
        $validData['url'] = '/new-nav-'.$uniqueSuffix;

        asUser($this->admin)
            ->post(route('navigation.store'), $validData)
            ->assertStatus(302)
            ->assertRedirect(route('navigation.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', [
            'name' => $validData['name'],
            'url' => $validData['url'],
            'parent_id' => $validData['parent_id'],
            'lang' => $validData['lang'],
        ]);
    });

    test('navigation is created with correct order', function (): void {
        // Create another navigation with the same parent
        Navigation::factory()->create([
            'parent_id' => 0,
            'order' => 5,
        ]);

        $validData = getControllerTestData('Navigation')['valid'];
        $validData['name'] = 'Navigation with order';
        $validData['parent_id'] = 0;

        asUser($this->admin)
            ->post(route('navigation.store'), $validData)
            ->assertStatus(302);

        $navigation = Navigation::where('name', 'Navigation with order')->first();
        expect($navigation->order)->toBe(6); // Should be max(5) + 1
    });

    test('child navigation inherits parent language', function (): void {
        $parentNav = Navigation::factory()->create([
            'lang' => 'en',
            'parent_id' => 0,
        ]);

        $validData = getControllerTestData('Navigation')['valid'];
        $validData['parent_id'] = $parentNav->id;
        $validData['name'] = 'Child navigation';

        asUser($this->admin)
            ->post(route('navigation.store'), $validData)
            ->assertStatus(302);

        $this->assertDatabaseHas('navigation', [
            'name' => 'Child navigation',
            'parent_id' => $parentNav->id,
            'lang' => 'en', // Should inherit from parent
        ]);
    });

    test('a root can pick an explicit language independent of the request locale', function (): void {
        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => 'English root',
                'url' => '/english-root',
                'parent_id' => 0,
                'lang' => 'en',
                'is_active' => true,
                'extra_attributes' => [],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', [
            'name' => 'English root',
            'parent_id' => 0,
            'lang' => 'en',
        ]);
    });

    test('can access edit page', function (): void {
        asUser($this->admin)
            ->get(route('navigation.edit', $this->navigation))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Navigation/EditNavigation')
                ->has('navigationElement')
                ->has('parentElements')
                ->has('categoryOptions')
                ->where('navigationElement.id', $this->navigation->id)
            );
    });

    test('can update navigation with valid data', function (): void {
        $updateData = getControllerTestData('Navigation')['valid'];
        $updateData['name'] = 'Updated Navigation';
        $updateData['url'] = '/updated-nav';

        asUser($this->admin)
            ->patch(route('navigation.update', $this->navigation), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', [
            'id' => $this->navigation->id,
            'name' => 'Updated Navigation',
            'url' => '/updated-nav',
        ]);
    });

    test('can toggle is_active on a root navigation item', function (): void {
        // Roots are ordinary `navigation` rows with `parent_id = 0` and `url = '#'` —
        // the builder's new root-level Switch (NavigationRootItem.vue) patches through
        // the same `navigation.update` route as any child link.
        $root = Navigation::factory()->create([
            'name' => 'Root Item',
            'url' => '#',
            'parent_id' => 0,
            'lang' => 'lt',
            'is_active' => true,
        ]);

        asUser($this->admin)
            ->patch(route('navigation.update', $root), [
                'name' => $root->name,
                'url' => $root->url,
                'parent_id' => 0,
                'lang' => 'lt',
                'is_active' => false,
                'extra_attributes' => [],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', [
            'id' => $root->id,
            'is_active' => false,
        ]);
    });

    test('can update navigation extra_attributes presentation fields', function (): void {
        $updateData = getControllerTestData('Navigation')['valid'];
        $updateData['extra_attributes'] = [
            'type' => 'full-height-background-link',
            'image' => 'https://example.com/hero.jpg',
            'image_render' => 'card',
            'image_overlay' => 'heavy',
            'image_blur' => 4,
            'image_focal' => '30% 70%',
            'image_gradient' => 'full',
            'featured' => true,
            'new_tab' => true,
            'badge_variant' => 'emerald',
            'col_span' => 2,
        ];

        asUser($this->admin)
            ->patch(route('navigation.update', $this->navigation), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->navigation->refresh();
        expect($this->navigation->extra_attributes)
            ->toMatchArray([
                'type' => 'full-height-background-link',
                'image_overlay' => 'heavy',
                'image_blur' => 4,
                'image_focal' => '30% 70%',
                'featured' => true,
                'new_tab' => true,
                'badge_variant' => 'emerald',
                'col_span' => 2,
            ]);
    });

    test('rejects an invalid image_focal format', function (): void {
        $updateData = getControllerTestData('Navigation')['valid'];
        $updateData['extra_attributes'] = ['image_focal' => 'centered'];

        asUser($this->admin)
            ->patch(route('navigation.update', $this->navigation), $updateData)
            ->assertSessionHasErrors(['extra_attributes.image_focal']);
    });

    test('rejects an invalid badge_variant', function (): void {
        $updateData = getControllerTestData('Navigation')['valid'];
        $updateData['extra_attributes'] = ['badge_variant' => 'purple'];

        asUser($this->admin)
            ->patch(route('navigation.update', $this->navigation), $updateData)
            ->assertSessionHasErrors(['extra_attributes.badge_variant']);
    });

    test('a heading type does not require a name, same as a divider', function (): void {
        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => null,
                'url' => '#',
                'parent_id' => 0,
                'is_active' => true,
                'extra_attributes' => ['type' => 'heading'],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');
    });

    test('can delete navigation', function (): void {
        asUser($this->admin)
            ->delete(route('navigation.destroy', $this->navigation))
            ->assertStatus(302)
            ->assertRedirect(route('navigation.index'))
            ->assertSessionHas('info');

        $this->assertSoftDeleted('navigation', [
            'id' => $this->navigation->id,
        ]);
    });

    test('show deleted index returns only trashed navigation records', function (): void {
        $trashedChild = Navigation::factory()->create([
            'name' => 'Deleted Child Navigation',
            'url' => '/deleted-child',
            'parent_id' => $this->navigation->id,
            'order' => 2,
            'lang' => 'lt',
        ]);
        $trashedChild->delete();

        $response = asUser($this->admin)
            ->get(route('navigation.index', ['showDeleted' => 'true']));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Navigation/IndexNavigation')
                ->where('showDeleted', true)
                ->where('deletedCount', 1)
                ->has('navigation')
            );

        $ids = collect($response->viewData('page')['props']['navigation'])->pluck('id');

        expect($ids)->toContain($trashedChild->id)
            ->and($ids)->not->toContain($this->navigation->id);
    });

    test('deleted count only includes records for the current language', function (): void {
        $deletedLithuanianNavigation = Navigation::factory()->create(['lang' => 'lt']);
        $deletedLithuanianNavigation->delete();

        $deletedEnglishNavigation = Navigation::factory()->create(['lang' => 'en']);
        $deletedEnglishNavigation->delete();

        asUser($this->admin)
            ->get(route('navigation.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('deletedCount', 1));
    });

    test('can store divider navigation without name', function (): void {
        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => null,
                'url' => '#',
                'parent_id' => 0,
                'is_active' => true,
                'extra_attributes' => ['type' => 'divider'],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', [
            'url' => '#',
            'parent_id' => 0,
        ]);
    });

    test('null parent_id is coerced to zero', function (): void {
        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => 'Root from null parent',
                'url' => '/root',
                'parent_id' => null,
                'is_active' => true,
                'extra_attributes' => [],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', [
            'name' => 'Root from null parent',
            'url' => '/root',
            'parent_id' => 0,
        ]);
    });

    test('non-divider navigation requires name', function (): void {
        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => '',
                'url' => '/missing-name',
                'parent_id' => 0,
                'is_active' => true,
                'extra_attributes' => ['type' => 'link'],
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    });
});

describe('footer navigation', function (): void {
    test('index exposes the footer tree separately from the header tree', function (): void {
        $footerRoot = Navigation::factory()->create([
            'name' => 'Footer column',
            'url' => '#',
            'parent_id' => 0,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        $response = asUser($this->admin)->get(route('navigation.index'));

        $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page->has('footerNavigation'));

        $footerIds = collect($response->viewData('page')['props']['footerNavigation'])->pluck('id');
        $headerIds = collect($response->viewData('page')['props']['navigation'])->pluck('id');

        expect($footerIds)->toContain($footerRoot->id)
            ->and($headerIds)->not->toContain($footerRoot->id);
    });

    test('creating a footer column stores its location and forces the category-link type', function (): void {
        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => 'Footer column',
                'url' => '',
                'parent_id' => 0,
                'lang' => 'lt',
                'is_active' => true,
                'extra_attributes' => ['location' => 'footer'],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $column = Navigation::where('name', 'Footer column')->firstOrFail();

        expect($column->extra_attributes)->toMatchArray([
            'location' => 'footer',
            'type' => 'category-link',
        ]);
    });

    test('a footer column may be saved without a URL — it renders as plain text', function (): void {
        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => 'Text-only heading',
                'parent_id' => 0,
                'lang' => 'lt',
                'is_active' => true,
                'extra_attributes' => ['location' => 'footer'],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', ['name' => 'Text-only heading', 'url' => '']);
    });

    test('a footer link under a column is forced to the plain link type, even if the client sends something else', function (): void {
        $footerRoot = Navigation::factory()->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => 'Footer link',
                'url' => '/footer-link',
                'parent_id' => $footerRoot->id,
                'lang' => 'lt',
                'is_active' => true,
                'extra_attributes' => ['location' => 'footer', 'type' => 'full-height-background-link'],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $link = Navigation::where('name', 'Footer link')->firstOrFail();
        expect($link->extra_attributes['type'])->toBe('link');
    });

    test('a footer link still requires a URL', function (): void {
        $footerRoot = Navigation::factory()->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => 'Footer link',
                'url' => '',
                'parent_id' => $footerRoot->id,
                'lang' => 'lt',
                'is_active' => true,
                'extra_attributes' => ['location' => 'footer'],
            ])
            ->assertSessionHasErrors(['url']);
    });

    test('a 5th footer column is rejected', function (): void {
        Navigation::factory()->count(4)->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        asUser($this->admin)
            ->post(route('navigation.store'), [
                'name' => 'One too many',
                'parent_id' => 0,
                'lang' => 'lt',
                'is_active' => true,
                'extra_attributes' => ['location' => 'footer'],
            ])
            ->assertSessionHasErrors(['extra_attributes.location']);
    });

    test('the 5th column check ignores the row being updated', function (): void {
        $columns = Navigation::factory()->count(4)->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        asUser($this->admin)
            ->patch(route('navigation.update', $columns->first()), [
                'name' => 'Renamed column',
                'url' => '',
                'parent_id' => 0,
                'lang' => 'lt',
                'is_active' => true,
                'extra_attributes' => ['location' => 'footer'],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('navigation', ['id' => $columns->first()->id, 'name' => 'Renamed column']);
    });

    test('create page scopes parentElements to footer columns only when creating a footer link', function (): void {
        $footerRoot = Navigation::factory()->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        $response = asUser($this->admin)
            ->get(route('navigation.create', ['parent_id' => $footerRoot->id]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('location', 'footer'));

        $parentIds = collect($response->viewData('page')['props']['parentElements'])->pluck('id');

        expect($parentIds)->toContain($footerRoot->id)
            ->and($parentIds)->not->toContain($this->navigation->id);
    });

    test('edit page scopes parentElements to the item\'s own menu location', function (): void {
        $footerRoot = Navigation::factory()->create(['parent_id' => 0, 'lang' => 'lt', 'extra_attributes' => ['location' => 'footer', 'type' => 'category-link']]);
        $footerLink = Navigation::factory()->create(['parent_id' => $footerRoot->id, 'lang' => 'lt', 'extra_attributes' => ['location' => 'footer', 'type' => 'link']]);

        $response = asUser($this->admin)
            ->get(route('navigation.edit', $footerLink))
            ->assertStatus(200);

        $parentIds = collect($response->viewData('page')['props']['parentElements'])->pluck('id');

        expect($parentIds)->toContain($footerRoot->id)
            ->and($parentIds)->not->toContain($this->navigation->id);
    });
});

describe('navigation ordering functionality', function (): void {
    beforeEach(function (): void {
        // Create a navigation structure for testing
        $this->parentNav = Navigation::factory()->create([
            'name' => 'Parent Navigation',
            'parent_id' => 0,
            'order' => 1,
        ]);

        $this->childNav1 = Navigation::factory()->create([
            'name' => 'Child 1',
            'parent_id' => $this->parentNav->id,
            'order' => 1,
            'extra_attributes' => ['column' => 1],
        ]);

        $this->childNav2 = Navigation::factory()->create([
            'name' => 'Child 2',
            'parent_id' => $this->parentNav->id,
            'order' => 2,
            'extra_attributes' => ['column' => 1],
        ]);
    });

    test('can update navigation order', function (): void {
        $orderData = [
            'navigation' => [
                [
                    'id' => $this->parentNav->id,
                    'links' => [
                        [
                            ['id' => $this->childNav2->id], // Child 2 first
                            ['id' => $this->childNav1->id], // Child 1 second
                        ],
                    ],
                ],
            ],
        ];

        asUser($this->admin)
            ->post(route('navigation.updateOrder'), $orderData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $child2 = Navigation::find($this->childNav2->id);
        $child1 = Navigation::find($this->childNav1->id);

        expect($child2->order)->toBeLessThan($child1->order);
    });

    test('updateOrder moves a link into a different column without colliding with the other column', function (): void {
        // Both children start in column 1; move child 2 to column 2 while child 1 stays —
        // `links` position 0 is column 1, position 1 is column 2.
        $orderData = [
            'navigation' => [
                [
                    'id' => $this->parentNav->id,
                    'links' => [
                        [
                            ['id' => $this->childNav1->id],
                        ],
                        [
                            ['id' => $this->childNav2->id],
                        ],
                    ],
                ],
            ],
        ];

        asUser($this->admin)
            ->post(route('navigation.updateOrder'), $orderData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->childNav1->refresh();
        $this->childNav2->refresh();

        expect($this->childNav1->extra_attributes['column'])->toBe(1)
            ->and($this->childNav2->extra_attributes['column'])->toBe(2)
            ->and($this->childNav1->order)->toBe(0)
            ->and($this->childNav2->order)->toBe(0); // order resets per column, so both can be first
    });

    test('updateOrder does not crash when a link has a null extra_attributes blob', function (): void {
        $navWithoutAttributes = Navigation::factory()->create([
            'parent_id' => $this->parentNav->id,
            'order' => 3,
            'extra_attributes' => null,
        ]);

        asUser($this->admin)
            ->post(route('navigation.updateOrder'), [
                'navigation' => [
                    [
                        'id' => $this->parentNav->id,
                        'links' => [
                            [], // column 1 empty
                            [['id' => $navWithoutAttributes->id]], // column 2
                        ],
                    ],
                ],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $navWithoutAttributes->refresh();
        expect($navWithoutAttributes->extra_attributes['column'])->toBe(2);
    });

    test('updateOrder rejects more than 3 columns', function (): void {
        asUser($this->admin)
            ->post(route('navigation.updateOrder'), [
                'navigation' => [
                    [
                        'id' => $this->parentNav->id,
                        'links' => [
                            [['id' => $this->childNav1->id]],
                            [],
                            [],
                            [['id' => $this->childNav2->id]],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors(['navigation.0.links']);
    });

    test('updateOrder rejects a soft-deleted link id', function (): void {
        $this->childNav1->delete();

        asUser($this->admin)
            ->post(route('navigation.updateOrder'), [
                'navigation' => [
                    [
                        'id' => $this->parentNav->id,
                        'links' => [
                            [['id' => $this->childNav1->id]],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors(['navigation.0.links.0.0.id']);
    });
});
