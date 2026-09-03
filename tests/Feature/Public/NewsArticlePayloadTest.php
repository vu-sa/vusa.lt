<?php

use App\Models\Category;
use App\Models\News;
use App\Models\Page;
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

/**
 * The header's category chip reads `article.category.name`. `only('category')` resolves the whole
 * BelongsTo (Eloquent's `getAttribute` loads relations), so the payload is the Category model —
 * this pins the shape the frontend now depends on.
 */
test('the article payload carries its category', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $category = Category::factory()->create(['name' => 'Akademinė informacija']);
    $news = News::factory()->for($tenant)->for($category)->create(['lang' => 'lt']);

    $this->get(newsArticleUrl($news))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->where('article.category.name', 'Akademinė informacija')
        );
});

test('an article filed under no category ships a null rather than omitting the key', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create(['lang' => 'lt', 'category_id' => null]);

    $this->get(newsArticleUrl($news))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->where('article.category', null)
        );
});

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
 * the same fields it does — the old payload had neither an image nor a category.
 */
test('related articles carry the fields the news card renders', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $category = Category::factory()->create(['name' => 'Renginiai']);

    $news = News::factory()->for($tenant)->create(['lang' => 'lt']);
    News::factory()->for($tenant)->for($category)->create([
        'lang' => 'lt',
        'draft' => false,
        'publish_time' => now()->subDay(),
    ]);

    $this->get(newsArticleUrl($news))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('relatedArticles', 1, fn (Assert $related) => $related
                ->where('category', 'Renginiai')
                ->has('image')
                ->has('short')
                ->has('lang')
                ->has('permalink')
                ->has('publish_time')
                ->etc()
            )
        );
});

test('the content page payload carries its category name for the band eyebrow', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $category = Category::factory()->create(['name' => 'Studentams', 'alias' => 'studentams']);
    $pageModel = Page::factory()->for($tenant)->for($category)->create([
        'lang' => 'lt',
        'is_active' => true,
    ]);

    $this->get(route('page', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'permalink' => $pageModel->permalink,
    ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->where('page.category.name', 'Studentams')
        );
});
