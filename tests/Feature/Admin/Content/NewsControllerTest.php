<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();

    $this->user = makeUser($this->tenant);

    $this->news = News::factory()->create();

    $this->newsManager = User::factory()->create();

    $communicationCoordinatorDuty = Duty::factory()->has(Institution::factory()->state(
        ['tenant_id' => $this->tenant->id]
    ))->hasAttached($this->newsManager, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)])->create();

    $communicationCoordinatorDuty->assignRole('Communication Coordinator');
});

describe('auth: simple user', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('can\'t index news', function () {
        asUser($this->user)->get(route('news.index'))->assertStatus(403);
    });

    test('can\'t access news create page', function () {
        asUser($this->user)->get(route('news.create'))->assertStatus(403);
    });

    test('can\'t store news', function () {
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

    test('can\'t store news via inertia', function () {
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

    test('can\' t access the news edit page', function () {
        $news = News::query()->first();

        asUser($this->user)->get(route('news.edit', $news))->assertStatus(403);
    });

    test('can\'t update news', function () {
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

    test('can\'t delete news', function () {
        $news = News::query()->first();

        asUser($this->user)->delete(route('news.destroy', $news))->assertStatus(403);
    });
});

describe('auth: news manager', function () {
    beforeEach(function () {
        asUser($this->newsManager)->get(route('dashboard'))->assertStatus(200);
    });

    test('can index news', function () {
        asUser($this->newsManager)->get(route('news.index'))->assertStatus(200);
    });

    test('malicious filter key does not break the index query', function () {
        $maliciousFilters = json_encode(['tenant.shortname) OR 1=1 -- ' => 'x']);

        asUser($this->newsManager)
            ->get(route('news.index', ['filters' => $maliciousFilters]))
            ->assertStatus(200);
    });

    test('can access news create page', function () {
        asUser($this->newsManager)->get(route('news.create'))->assertStatus(200);
    });

    test('can store news', function () {
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

    test('show_breadcrumbs round-trips through store and update', function () {
        // Store with breadcrumbs disabled.
        $permalink = 'news-no-breadcrumbs-'.time();

        asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'News without breadcrumbs',
            'permalink' => $permalink,
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

    test('can access the news edit page', function () {
        $news = News::query()->first();

        asUser($this->newsManager)->get(route('news.edit', $news))->assertStatus(200);
    })->todo();

    test('can update news', function () {
        $news = News::query()->first();

        $response = asUser($this->newsManager)->get(route('news.edit', $news))->assertStatus(200);

        asUser($this->newsManager)->
            put(route('news.update', $news), [
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
            ])->assertStatus(302)->assertRedirectToRoute('news.index');
    })->todo();

    test('can delete news', function () {
        $news = News::query()->first();

        asUserWithInertia($this->newsManager)->delete(route('news.destroy', $news))->assertRedirect();
    });

    test('can duplicate news', function () {
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

    test('can duplicate news with tags', function () {
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

    test('rejects an invalid content part options.width value', function () {
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

    test('accepts a text-box content part with a translatable title object', function () {
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

    test('storing news without resolvable tenant returns validation error', function () {
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

    test('news permalink must be unique within tenant', function () {
        $managerTenant = $this->newsManager->duties()->first()->institution->tenant;

        News::factory()->for($managerTenant)->create([
            'permalink' => 'duplicate-news-permalink',
        ]);

        $response = asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'Duplicate News',
            'permalink' => 'duplicate-news-permalink',
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
    });

    test('news permalink can be reused across tenants', function () {
        $managerTenant = $this->newsManager->duties()->first()->institution->tenant;
        $otherTenant = Tenant::query()->where('id', '!=', $managerTenant->id)->firstOrFail();

        News::factory()->for($otherTenant)->create([
            'permalink' => 'shared-news-permalink',
        ]);

        $response = asUser($this->newsManager)->post(route('news.store'), [
            'title' => 'Shared News',
            'permalink' => 'shared-news-permalink',
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

        $this->assertDatabaseHas('news', [
            'permalink' => 'shared-news-permalink',
            'tenant_id' => $managerTenant->id,
        ]);
    });
});
