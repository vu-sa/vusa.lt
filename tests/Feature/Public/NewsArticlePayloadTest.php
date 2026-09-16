<?php

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

function newsArticleUrl(News $news): string
{
    return route('news', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'newsString' => 'naujiena',
        'news' => $news->permalink,
    ]);
}

test('the article payload carries a reading time', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create(['lang' => 'lt']);

    $this->get(newsArticleUrl($news))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->where('article.reading_time', fn ($minutes) => is_int($minutes) && $minutes >= 1)
        );
});

/** The four-way layout choice is gone — one article design, so nothing selects between them. */
test('the article payload no longer carries a layout', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create(['lang' => 'lt']);

    $this->get(newsArticleUrl($news))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->missing('article.layout')
        );
});

/**
 * Related articles render through the same `NewsCard` as the homepage's news block, so they need
 * the same fields it does — the old payload had no image.
 */
test('related articles carry the fields the news card renders', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();

    $news = News::factory()->for($tenant)->create(['lang' => 'lt']);
    News::factory()->for($tenant)->create([
        'lang' => 'lt',
        'draft' => false,
        'publish_time' => now()->subDay(),
    ]);

    $this->get(newsArticleUrl($news))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('relatedArticles', 1, fn (Assert $related) => $related
                ->has('image')
                ->has('short')
                ->has('lang')
                ->has('permalink')
                ->has('publish_time')
                ->etc()
            )
        );
});
