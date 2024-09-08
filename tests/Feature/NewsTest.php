<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
        asUser($this->user)->get(route('news.index'))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t access news create page', function () {
        asUser($this->user)->get(route('news.create'))->assertStatus(302);
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
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\' t access the news edit page', function () {
        $news = News::query()->first();

        asUser($this->user)->get(route('news.edit', $news))->assertStatus(302);
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
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t delete news', function () {
        $news = News::query()->first();

        asUser($this->user)->delete(route('news.destroy', $news))->assertStatus(302);
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

        asUser($this->newsManager)->delete(route('news.destroy', $news))->assertStatus(302);
    });

    test('can duplicate news', function () {
        $news = News::query()->first();

        // Send the POST request to duplicate the news
        $response = asUser($this->newsManager)->post(route('news.duplicate', $news))
            ->assertStatus(302);  // Assert the response status is 302

        // Get the latest news item
        $latestNews = News::query()->latest()->first();

        // Assert the redirect to the edit route of the latest news item
        $response->assertRedirect(route('news.edit', $latestNews));
    })->todo();
});
