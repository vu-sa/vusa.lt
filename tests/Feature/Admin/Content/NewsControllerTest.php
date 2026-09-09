<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\PublicUrl;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->user = makeUser($this->tenant);

    $this->news = News::factory()->create();

    $this->newsManager = User::factory()->create();

    $communicationCoordinatorDuty = Duty::factory()->has(Institution::factory()->state(
        ['tenant_id' => $this->tenant->id]
    ))->hasAttached($this->newsManager, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)])->create();

    $communicationCoordinatorDuty->assignRole('Communication Coordinator');
});

describe('auth: simple user', function (): void {
    beforeEach(function (): void {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('can\'t index news', function (): void {
        asUser($this->user)->get(route('news.index'))->assertStatus(403);
    });

    test('can\'t access news create page', function (): void {
        asUser($this->user)->get(route('news.create'))->assertStatus(403);
    });

    test('can\'t store news', function (): void {
        asUser($this->user)->post(route('news.store'), [
            'title' => 'News 1',
            'permalink' => 'news-1',
            'content' => [
                'parts' => [
                    [
                        'type' => 'text',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ])->assertStatus(403);
    });

    test('can\'t store news via inertia', function (): void {
        $response = asUser($this->user)->post(route('news.store'), [
            'title' => 'News 1',
            'permalink' => 'news-1',
            'content' => [
                'parts' => [
                    [
                        'type' => 'text',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ], [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => 'test-version',
        ]);

        $response->assertStatus(302)->assertSessionHas('error');
    });

    test('can\' t access the news edit page', function (): void {
        $news = News::query()->first();

        asUser($this->user)->get(route('news.edit', $news))->assertStatus(403);
    });

    test('can\'t update news', function (): void {
        $news = News::query()->first();

        asUser($this->user)->put(route('news.update', $news), [
            'title' => 'News 2',
            'permalink' => 'news-1',
            'content' => [
                'parts' => [
                    [
                        'type' => 'text',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ])->assertStatus(403);
    });

    test('can\'t delete news', function (): void {
        $news = News::query()->first();

        asUser($this->user)->delete(route('news.destroy', $news))->assertStatus(403);
    });
});

describe('auth: news manager', function (): void {
    beforeEach(function (): void {
        asUser($this->newsManager)->get(route('dashboard'))->assertStatus(200);
    });

    test('can index news', function (): void {
        asUser($this->newsManager)->get(route('news.index'))->assertStatus(200);
    });

    test('malicious filter key does not break the index query', function (): void {
        $maliciousFilters = json_encode(['tenant.shortname) OR 1=1 -- ' => 'x']);

        asUser($this->newsManager)
            ->get(route('news.index', ['filters' => $maliciousFilters]))
            ->assertStatus(200);
    });

    test('can access news create page', function (): void {
        asUser($this->newsManager)->get(route('news.create'))->assertStatus(200);
    });

    test('can store news', function (): void {
        asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'News 1',
            'permalink' => 'news-1',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ])->assertStatus(302)->assertRedirectToRoute('news.index');
    });

    test('show_breadcrumbs round-trips through store and update', function (): void {
        // Store with breadcrumbs disabled. The permalink is server-generated from the title
        // (GenerateUniqueSlug) — a submitted value is ignored on create.
        $permalink = 'news-without-breadcrumbs';

        asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'News without breadcrumbs',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
            'show_breadcrumbs' => false,
        ])->assertStatus(302)->assertRedirectToRoute('news.index');

        $this->assertDatabaseHas('news', [
            'permalink' => $permalink,
            'show_breadcrumbs' => false,
        ]);

        // Update it back to enabled — this news belongs to the manager's tenant, so
        // the update policy passes (the factory-created $this->news may not).
        $created = News::query()->where('permalink', $permalink)->first();

        asUser($this->newsManager)->put(route('news.update', $created), [
            'title' => $created->title,
            'permalink' => $created->permalink,
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
            'show_breadcrumbs' => true,
        ])->assertStatus(302);

        $this->assertDatabaseHas('news', [
            'id' => $created->id,
            'show_breadcrumbs' => true,
        ]);
    });

    test('can access the news edit page', function (): void {
        $news = News::query()->first();

        asUser($this->newsManager)->get(route('news.edit', $news))->assertStatus(200);
    })->todo();

    test('can update news', function (): void {
        $managerTenant = $this->newsManager->duties()->first()->institution->tenant;
        $news = News::factory()->for($managerTenant)->create();

        $response = asUser($this->newsManager)->get(route('news.edit', $news))->assertStatus(200);

        asUser($this->newsManager)->
            put(route('news.update', $news), [
                'title' => 'News 2',
                'permalink' => 'news-2',
                'content' => [
                    'parts' => [
                        [
                            'type' => 'tiptap',
                            'json_content' => ['lt' => 'News content'],
                            'options' => [],
                            'order' => 1,
                        ],
                    ],
                ],
                'lang' => 'lt',
                'image' => 'image.jpg',
                'publish_time' => now()->timestamp,
                'short' => 'Short news',
            ])->assertStatus(302)->assertSessionHas('success');

        // Regression: NewsController::update() previously omitted 'permalink' from its
        // $request->safe()->only(...) allowlist, so an edited permalink was silently dropped
        // even though UpdateNewsRequest validated it — the edit form appeared to work but never
        // persisted the change.
        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => 'News 2',
            'permalink' => 'news-2',
        ]);
    });

    test('can delete news', function (): void {
        $news = News::query()->first();

        asUserWithInertia($this->newsManager)->delete(route('news.destroy', $news))->assertRedirect();
    });

    test('can duplicate news', function (): void {
        $news = News::query()->first();
        $initialCount = News::count();

        // Send the POST request to duplicate the news
        $response = asUser($this->newsManager)->post(route('news.duplicate', $news))
            ->assertStatus(302);  // Assert the response status is 302

        // Verify a new news item was created
        expect(News::count())->toBe($initialCount + 1);

        // Verify redirect to edit page (any news edit page is fine)
        $response->assertRedirectContains('/mano/news/')
            ->assertRedirectContains('/edit');

        // Find the duplicated news (should have "(kopija)" in title and be in draft mode)
        $duplicatedNews = News::query()
            ->where('title', 'LIKE', '%'.$news->title.' (kopija)%')
            ->where('draft', 1)
            ->latest()
            ->first();

        // Verify the duplicated news exists and has expected properties
        expect($duplicatedNews)->not()->toBeNull()
            ->and($duplicatedNews->title)->toContain('(kopija)')
            ->and($duplicatedNews->draft)->toBe(1)
            ->and($duplicatedNews->publish_time)->toBeNull()
            ->and($duplicatedNews->id)->not()->toBe($news->id);
    });

    test('can duplicate news with tags', function (): void {
        $news = News::query()->first();

        // Add some tags to the original news
        $tags = Tag::factory()->count(2)->create();
        $news->tags()->attach($tags->pluck('id'));

        $initialCount = News::count();

        // Send the POST request to duplicate the news
        $response = asUser($this->newsManager)->post(route('news.duplicate', $news))
            ->assertStatus(302);

        // Verify a new news item was created
        expect(News::count())->toBe($initialCount + 1);

        // Find the duplicated news
        $duplicatedNews = News::query()
            ->where('draft', 1)
            ->latest()
            ->first();

        // Load tags relationship
        $duplicatedNews->load('tags');
        $news->load('tags');

        // Verify tags were copied
        expect($duplicatedNews->tags)->toHaveCount(2)
            ->and($duplicatedNews->tags->pluck('id')->sort()->values()->toArray())
            ->toBe($news->tags->pluck('id')->sort()->values()->toArray());
    });

    test('rejects an invalid content part options.width value', function (): void {
        $response = asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'News with bad width',
            'permalink' => 'news-bad-width',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => ['width' => 'enormous'],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ]);

        $response->assertStatus(302)->assertSessionHasErrors(['content.parts.0.options.width']);
    });

    test('accepts a text-box content part with a translatable title object', function (): void {
        $response = asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'News with text-box',
            'permalink' => 'news-text-box-title-'.time(),
            'content' => [
                'parts' => [
                    [
                        'type' => 'text-box',
                        'json_content' => [],
                        'options' => [
                            'title' => ['lt' => 'Klausimas', 'en' => 'Question'],
                            'placeholder' => ['lt' => 'Atsakykite...', 'en' => 'Answer...'],
                            'isClosed' => false,
                            'closedMessage' => ['lt' => 'Uždaryta', 'en' => 'Closed'],
                        ],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ]);

        $response->assertStatus(302)->assertSessionDoesntHaveErrors();
    });

    test('storing news without resolvable tenant returns validation error', function (): void {
        // Create a manager whose duty institution has no tenant, so tenant_id cannot be resolved
        $orphanManager = User::factory()->create();
        $institution = Institution::factory()->create(['tenant_id' => null]);
        $duty = Duty::factory()->for($institution)
            ->hasAttached($orphanManager, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)])
            ->create();
        $duty->assignRole('Communication Coordinator');

        $initialCount = News::count();

        $response = asUser($orphanManager)->post(route('news.store'), [
            'title' => 'Orphan News',
            'permalink' => 'orphan-news',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['tenant_id']);
        expect(News::count())->toBe($initialCount);
    });

    test('a colliding title within the same tenant gets a suffixed permalink instead of a validation error', function (): void {
        $managerTenant = $this->newsManager->duties()->first()->institution->tenant;

        // Occupies the exact slug 'Duplicate News' would produce, forcing a genuine collision.
        News::factory()->for($managerTenant)->create(['permalink' => 'duplicate-news']);

        $response = asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'Duplicate News',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ]);

        $response->assertStatus(302)->assertSessionDoesntHaveErrors('permalink')->assertSessionHas('success');

        $this->assertDatabaseHas('news', [
            'title' => 'Duplicate News',
            'permalink' => 'duplicate-news-2',
            'tenant_id' => $managerTenant->id,
        ]);
    });

    test('the same title-derived permalink can exist in two different tenants', function (): void {
        $managerTenant = $this->newsManager->duties()->first()->institution->tenant;
        $otherTenant = Tenant::query()->where('id', '!=', $managerTenant->id)->firstOrFail();

        // Created directly (not through the controller): a non-super-admin actor's own tenant
        // always wins regardless of a submitted tenant_id, so this is the only way to seed a
        // colliding slug in a tenant $this->newsManager doesn't belong to.
        News::factory()->for($otherTenant)->create(['permalink' => 'shared-news']);

        $response = asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'Shared News',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ]);

        $response->assertStatus(302)
            ->assertRedirectToRoute('news.index')
            ->assertSessionHas('success');

        // Scoped per tenant: the other tenant's identical slug doesn't force a suffix here.
        $this->assertDatabaseHas('news', [
            'permalink' => 'shared-news',
            'tenant_id' => $managerTenant->id,
        ]);
        $this->assertDatabaseHas('news', [
            'permalink' => 'shared-news',
            'tenant_id' => $otherTenant->id,
        ]);
    });
});

describe('public URL history', function (): void {
    beforeEach(function (): void {
        $managerTenant = $this->newsManager->duties()->first()->institution->tenant;
        $this->managedNews = News::factory()->for($managerTenant)->create();
    });

    test('can delete a legacy public url', function (): void {
        $legacy = PublicUrl::factory()->create([
            'urlable_type' => $this->managedNews->getMorphClass(),
            'urlable_id' => $this->managedNews->id,
        ]);

        asUser($this->newsManager)
            ->delete(route('news.publicUrls.destroy', [$this->managedNews, $legacy]))
            ->assertStatus(302)
            ->assertSessionHas('info');

        $this->assertDatabaseMissing('public_urls', ['id' => $legacy->id]);
    });

    test('cannot delete a public url belonging to another news article', function (): void {
        $otherNews = News::factory()->create();
        $foreign = PublicUrl::factory()->create([
            'urlable_type' => $otherNews->getMorphClass(),
            'urlable_id' => $otherNews->id,
        ]);

        asUser($this->newsManager)
            ->delete(route('news.publicUrls.destroy', [$this->managedNews, $foreign]))
            ->assertStatus(403);

        $this->assertDatabaseHas('public_urls', ['id' => $foreign->id]);
    });

    test('user without update permission cannot delete a public url', function (): void {
        $legacy = PublicUrl::factory()->create([
            'urlable_type' => $this->managedNews->getMorphClass(),
            'urlable_id' => $this->managedNews->id,
        ]);

        asUser($this->user)
            ->delete(route('news.publicUrls.destroy', [$this->managedNews, $legacy]))
            ->assertStatus(403);

        $this->assertDatabaseHas('public_urls', ['id' => $legacy->id]);
    });

    test('cannot claim a permalink another news article has already retired', function (): void {
        $managerTenant = $this->newsManager->duties()->first()->institution->tenant;
        $original = News::factory()->for($managerTenant)->create(['permalink' => 'the-original-slug']);
        // Retires 'the-original-slug' into public_urls, owned by $original.
        $original->update(['permalink' => 'moved-on']);

        $response = asUser($this->newsManager)->put(route('news.update', $this->managedNews), [
            'title' => $this->managedNews->title,
            'permalink' => 'the-original-slug',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ]);

        $response->assertStatus(302)->assertSessionHasErrors(['permalink']);
        $this->assertDatabaseMissing('news', ['id' => $this->managedNews->id, 'permalink' => 'the-original-slug']);
    });

    test('a news article can re-adopt its own retired permalink', function (): void {
        $originalPermalink = $this->managedNews->permalink;
        $this->managedNews->update(['permalink' => 'went-away']);
        $this->managedNews->refresh();

        $response = asUser($this->newsManager)->put(route('news.update', $this->managedNews), [
            'title' => $this->managedNews->title,
            'permalink' => $originalPermalink,
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap',
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'image.jpg',
            'publish_time' => now()->timestamp,
            'short' => 'Short news',
        ]);

        $response->assertStatus(302)->assertSessionDoesntHaveErrors('permalink');
        $this->assertDatabaseHas('news', ['id' => $this->managedNews->id, 'permalink' => $originalPermalink]);
    });
});
