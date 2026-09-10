<?php

use App\Models\Page;
use App\Models\PublicUrl;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->page = Page::factory()->for($this->tenant)->create([
        'title' => 'Test puslapis',
        'permalink' => 'test-page',
        'lang' => 'lt',
    ]);
});

describe('unauthorized access', function (): void {
    test('cannot access index page', function (): void {
        asUser($this->user)
            ->get(route('pages.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function (): void {
        asUser($this->user)
            ->get(route('pages.create'))
            ->assertStatus(403);
    });

    test('cannot store page', function (): void {
        $validData = getControllerTestData('Page')['valid'];
        $validData['tenant_id'] = $this->tenant->id;

        asUser($this->user)
            ->post(route('pages.store'), $validData)
            ->assertStatus(403);
    });

    test('cannot access edit page', function (): void {
        asUser($this->user)
            ->get(route('pages.edit', $this->page))
            ->assertStatus(403);
    });

    test('cannot update page', function (): void {
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->user)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(403);
    });

    test('cannot delete page', function (): void {
        asUser($this->user)
            ->delete(route('pages.destroy', $this->page))
            ->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    test('can access index page', function (): void {
        asUser($this->admin)
            ->get(route('pages.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages')
                ->has('pages.data')
            );
    });

    test('can access create page', function (): void {
        asUser($this->admin)
            ->get(route('pages.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreatePage')
                ->has('tenants')
                ->has('assignableTenants', 1)
                ->where('assignableTenants.0.id', $this->tenant->id)
            );
    });

    test('can store page with valid data', function (): void {
        $validData = getControllerTestData('Page')['valid'];
        $validData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $validData)
            ->assertStatus(302)
            ->assertRedirect(route('pages.index'))
            ->assertSessionHas('success');

        // The permalink is no longer client-supplied — the server derives it from the title
        // (GenerateUniqueSlug), so this asserts what it actually generates, not a fixed literal.
        $this->assertDatabaseHas('pages', [
            'title' => $validData['title'],
            'permalink' => Str::slug($validData['title']),
            'lang' => $validData['lang'],
            'is_active' => $validData['is_active'],
            'tenant_id' => $this->tenant->id,
        ]);
    });

    test('cannot store page with invalid data', function (): void {
        $invalidData = getControllerTestData('Page')['invalid'];
        $invalidData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(getControllerValidationErrors('Page'));
    });

    test('can access edit page', function (): void {
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

    test('can update page with valid data', function (): void {
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

    test('show_table_of_contents round-trips through store and update', function (): void {
        $validData = getControllerTestData('Page')['valid'];
        $validData['tenant_id'] = $this->tenant->id;
        $validData['show_table_of_contents'] = false;

        asUser($this->admin)
            ->post(route('pages.store'), $validData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'permalink' => Str::slug($validData['title']),
            'show_table_of_contents' => false,
        ]);

        // $this->page was created without an explicit value, so it holds the DB
        // default (true) — flipping it to false is the meaningful transition to assert.
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['tenant_id'] = $this->tenant->id;
        $updateData['show_table_of_contents'] = false;

        asUser($this->admin)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(302);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'show_table_of_contents' => false,
        ]);
    });

    test('show_breadcrumbs round-trips through store and update', function (): void {
        $validData = getControllerTestData('Page')['valid'];
        $validData['tenant_id'] = $this->tenant->id;
        $validData['show_breadcrumbs'] = false;

        asUser($this->admin)
            ->post(route('pages.store'), $validData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'permalink' => Str::slug($validData['title']),
            'show_breadcrumbs' => false,
        ]);

        // $this->page was created without an explicit value, so it holds the DB
        // default (true) — flipping it to false is the meaningful transition to assert.
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['tenant_id'] = $this->tenant->id;
        $updateData['show_breadcrumbs'] = false;

        asUser($this->admin)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(302);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'show_breadcrumbs' => false,
        ]);
    });

    test('cannot update page with invalid data', function (): void {
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

    test('can delete page', function (): void {
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

describe('filtering and search', function (): void {
    beforeEach(function (): void {
        // Create additional pages for testing
        Page::factory()->for($this->tenant)->create([
            'title' => 'Another page',
            'permalink' => 'another-page',
            'lang' => 'en',
        ]);
    });

    test('can filter pages by search term', function (): void {
        asUser($this->admin)
            ->get(route('pages.index', ['search' => 'Test']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages.data')
                ->where('pages.data', fn ($data) => collect($data)->contains(fn ($page) => str_contains($page['title'], 'Test')))
            );
    });

    test('can filter pages by language', function (): void {
        asUser($this->admin)
            ->get(route('pages.index', ['filters' => json_encode(['lang' => ['en']])]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages.data')
                ->where('pages.data', fn ($data) => collect($data)->every(fn ($page) => $page['lang'] === 'en'))
            );
    });
});

describe('edge cases and business logic', function (): void {
    test('a colliding title within the same tenant gets a suffixed permalink instead of a validation error', function (): void {
        // An existing page already occupies the exact slug the new title would produce, so this
        // forces a genuine collision (unlike $this->page, whose 'test-page' permalink was set
        // explicitly by its factory and doesn't itself match Str::slug('Test puslapis')).
        Page::factory()->for($this->tenant)->create(['permalink' => 'test-puslapis']);

        $duplicateData = getControllerTestData('Page')['valid'];
        $duplicateData['title'] = 'Test puslapis';
        $duplicateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $duplicateData)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors('permalink')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'title' => 'Test puslapis',
            'permalink' => 'test-puslapis-2',
            'tenant_id' => $this->tenant->id,
        ]);
    });

    test('the same title-derived permalink can exist in two different tenants', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();

        // Created directly (not through the controller): a non-super-admin actor's own tenant
        // always wins regardless of a submitted tenant_id, so this is the only way to seed a
        // colliding slug in a tenant $this->admin doesn't belong to.
        Page::factory()->for($otherTenant)->create(['permalink' => 'test-puslapis']);

        $validData = getControllerTestData('Page')['valid'];
        $validData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $validData)
            ->assertStatus(302)
            ->assertRedirect(route('pages.index'))
            ->assertSessionHas('success');

        // Scoped per tenant: the other tenant's identical slug doesn't force a suffix here.
        $this->assertDatabaseHas('pages', [
            'permalink' => Str::slug($validData['title']),
            'tenant_id' => $this->tenant->id,
        ]);
        $this->assertDatabaseHas('pages', [
            'permalink' => 'test-puslapis',
            'tenant_id' => $otherTenant->id,
        ]);
    });

    test('can update page permalink', function (): void {
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['permalink'] = 'updated-permalink';
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'permalink' => 'updated-permalink',
        ]);
    });

    test('page handles special characters in content', function (): void {
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
        $specialCharsData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->post(route('pages.store'), $specialCharsData)
            ->assertStatus(302)
            ->assertRedirect(route('pages.index'));

        // Str::slug transliterates Lithuanian diacritics on its own — no explicit permalink needed.
        $this->assertDatabaseHas('pages', [
            'title' => 'Puslapis su šiaudiniais žodžiais',
            'permalink' => 'puslapis-su-siaudiniais-zodziais',
        ]);
    });
});

describe('content part width validation', function (): void {
    test('accepts a valid options.width value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'width-valid-'.time();
        $data['content']['parts'][0]['options'] = ['width' => 'wide'];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });

    test('rejects an invalid options.width value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'width-invalid-'.time();
        $data['content']['parts'][0]['options'] = ['width' => 'gigantic'];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.0.options.width']);
    });

    test('rejects a non-array options value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'width-scalar-'.time();
        $data['content']['parts'][0]['options'] = 'not-an-array';

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.0.options']);
    });

    test('accepts plain presentation with its own padding', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'presentation-valid-'.time();
        $data['content']['parts'][0]['options'] = ['presentation' => 'plain', 'plainPadding' => 'compact'];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });

    test('rejects an invalid options.presentation value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'presentation-invalid-'.time();
        $data['content']['parts'][0]['options'] = ['presentation' => 'huge'];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.0.options.presentation']);
    });

    test('rejects the removed emphasis presentation', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'presentation-emphasis-'.time();
        $data['content']['parts'][0]['options'] = ['presentation' => 'emphasis'];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.0.options.presentation']);
    });

    test('rejects an invalid plain padding value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'plain-padding-invalid-'.time();
        $data['content']['parts'][0]['options'] = ['presentation' => 'plain', 'plainPadding' => 'huge'];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.0.options.plainPadding']);
    });

    test('accepts a section block with wraps/inner options', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'section-valid-'.time();
        $data['content']['parts'][] = [
            'type' => 'section',
            'json_content' => [],
            'options' => ['title' => 'Skyrius', 'wraps' => 'following', 'inner' => 'wide'],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });

    test('rejects an invalid section wraps value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'section-invalid-'.time();
        $data['content']['parts'][] = [
            'type' => 'section',
            'json_content' => [],
            'options' => ['wraps' => 'everything'],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.1.options.wraps']);
    });

    test('accepts a content-grid with verticalAlign', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'grid-align-valid-'.time();
        $data['content']['parts'][] = [
            'type' => 'content-grid',
            'json_content' => [],
            'options' => ['verticalAlign' => 'center'],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });

    test('rejects an invalid content-grid verticalAlign value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'grid-align-invalid-'.time();
        $data['content']['parts'][] = [
            'type' => 'content-grid',
            'json_content' => [],
            'options' => ['verticalAlign' => 'top'],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.1.options.verticalAlign']);
    });

    test('accepts a manual link-list link with an imageUrl', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'link-image-valid-'.time();
        $data['content']['parts'][] = [
            'type' => 'link-list',
            'json_content' => ['links' => [['title' => 'Nuoroda', 'url' => 'https://vusa.lt', 'imageUrl' => '/uploads/foto.png']]],
            'options' => ['source' => 'manual', 'style' => 'photo'],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });

    test('accepts event-list tenantLabelStyle', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'event-label-style-valid-'.time();
        $data['content']['parts'][] = [
            'type' => 'event-list',
            'json_content' => [],
            'options' => ['groupBy' => 'tenant', 'tenantLabelStyle' => 'faculty'],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });

    test('rejects an invalid event-list tenantLabelStyle value', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'event-label-style-invalid-'.time();
        $data['content']['parts'][] = [
            'type' => 'event-list',
            'json_content' => [],
            'options' => ['tenantLabelStyle' => 'medium'],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['content.parts.1.options.tenantLabelStyle']);
    });

    test('accepts a text-box with a translatable title object', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'textbox-title-valid-'.time();
        $data['content']['parts'][] = [
            'type' => 'text-box',
            'json_content' => [],
            'options' => [
                'title' => ['lt' => 'Klausimas', 'en' => 'Question'],
                'placeholder' => ['lt' => 'Atsakykite...', 'en' => 'Answer...'],
                'isClosed' => true,
                'closedMessage' => ['lt' => 'Uždaryta', 'en' => 'Closed'],
            ],
        ];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });

    test('still accepts a plain string options.title for section chrome', function (): void {
        $data = getControllerTestData('Page')['valid'];
        $data['tenant_id'] = $this->tenant->id;
        $data['permalink'] = 'textbox-string-title-valid-'.time();
        $data['content']['parts'][0]['options'] = ['title' => 'Paprastas pavadinimas'];

        asUser($this->admin)
            ->post(route('pages.store'), $data)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });
});

describe('public URL history', function (): void {
    test('can delete a legacy public url', function (): void {
        $legacy = PublicUrl::factory()->create([
            'urlable_type' => $this->page->getMorphClass(),
            'urlable_id' => $this->page->id,
        ]);

        asUser($this->admin)
            ->delete(route('pages.publicUrls.destroy', [$this->page, $legacy]))
            ->assertStatus(302)
            ->assertSessionHas('info');

        $this->assertDatabaseMissing('public_urls', ['id' => $legacy->id]);
    });

    test('cannot delete a public url belonging to another page', function (): void {
        $otherPage = Page::factory()->for($this->tenant)->create();
        $foreign = PublicUrl::factory()->create([
            'urlable_type' => $otherPage->getMorphClass(),
            'urlable_id' => $otherPage->id,
        ]);

        asUser($this->admin)
            ->delete(route('pages.publicUrls.destroy', [$this->page, $foreign]))
            ->assertStatus(403);

        $this->assertDatabaseHas('public_urls', ['id' => $foreign->id]);
    });

    test('user without update permission cannot delete a public url', function (): void {
        $legacy = PublicUrl::factory()->create([
            'urlable_type' => $this->page->getMorphClass(),
            'urlable_id' => $this->page->id,
        ]);

        asUser($this->user)
            ->delete(route('pages.publicUrls.destroy', [$this->page, $legacy]))
            ->assertStatus(403);

        $this->assertDatabaseHas('public_urls', ['id' => $legacy->id]);
    });

    test('cannot claim a permalink another page has already retired', function (): void {
        // 'lang' set explicitly: a freshly-created model's lang attribute isn't hydrated from
        // the column's DB default until refreshed, and the legacy-write guard in Page::booted()
        // requires it — see NewsController::update()'s equivalent regression test.
        $original = Page::factory()->for($this->tenant)->create(['permalink' => 'the-original-slug', 'lang' => 'lt']);
        // Retires 'the-original-slug' into public_urls, owned by $original.
        $original->update(['permalink' => 'moved-on']);

        $updateData = getControllerTestData('Page')['valid'];
        $updateData['permalink'] = 'the-original-slug';
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['permalink']);

        $this->assertDatabaseMissing('pages', ['id' => $this->page->id, 'permalink' => 'the-original-slug']);
    });

    test('a page can re-adopt its own retired permalink', function (): void {
        $originalPermalink = $this->page->permalink;
        $this->page->update(['permalink' => 'went-away']);
        $this->page->refresh();

        $updateData = getControllerTestData('Page')['valid'];
        $updateData['permalink'] = $originalPermalink;
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->patch(route('pages.update', $this->page), $updateData)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors('permalink');

        $this->assertDatabaseHas('pages', ['id' => $this->page->id, 'permalink' => $originalPermalink]);
    });
});

describe('tenant isolation', function (): void {
    beforeEach(function (): void {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherPage = Page::factory()->for($this->otherTenant)->create();
        $this->otherAdmin = makeTenantUserWithRole('Communication Coordinator', $this->otherTenant);
    });

    test('user only sees pages from their tenant', function (): void {
        asUser($this->admin)
            ->get(route('pages.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexPages')
                ->has('pages.data')
                ->where('pages.data', fn ($data) => collect($data)->every(fn ($page) => $page['tenant_id'] === $this->tenant->id))
            );
    });

    test('cannot access other tenant page', function (): void {
        asUser($this->admin)
            ->get(route('pages.edit', $this->otherPage))
            ->assertStatus(403); // Authorization failure - cannot access other tenant's page
    });

    test('cannot update other tenant page', function (): void {
        $updateData = getControllerTestData('Page')['valid'];
        $updateData['tenant_id'] = $this->tenant->id;

        asUser($this->admin)
            ->patch(route('pages.update', $this->otherPage), $updateData)
            ->assertStatus(403); // Authorization failure - cannot update other tenant's page
    });
});
