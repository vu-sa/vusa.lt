<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

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
        $tags = \App\Models\Tag::factory()->count(2)->create();
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
});
