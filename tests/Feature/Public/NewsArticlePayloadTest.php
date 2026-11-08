<?php

use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

function newsArticleUrl(News $news): string
{
    return LocalizedRouteSlugs::route('news', [
        'subdomain' => 'www',
        'news' => $news->permalink,
    ], $news->lang);
}

test('the article payload has the public contract in each locale', function (string $locale): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create([
        'lang' => $locale,
        'short' => 'Article excerpt',
        'other_lang_id' => null,
        'main_points' => null,
        'read_more' => null,
        'highlights' => ['A highlight'],
    ]);
    $tag = Tag::factory()->create(['name' => ['lt' => 'Studijos', 'en' => 'Studies']]);
    $news->tags()->attach($tag);

    $this->get(newsArticleUrl($news))
        ->assertOk()
        ->assertViewHas('page', fn (array $page) => $page['props']['resolvedParts'] instanceof stdClass
            && get_object_vars($page['props']['resolvedParts']) === [])
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article', fn (Assert $article) => $article
                ->whereType('id', 'integer')
                ->whereType('title', 'string')
                ->whereType('short', 'string')
                ->where('short', 'Article excerpt')
                ->where('lang', $locale)
                ->whereType('other_lang_id', 'null')
                ->whereType('permalink', 'string|null')
                ->whereType('publish_time', 'string|null')
                ->whereType('content', 'array')
                ->whereType('important', 'integer')
                ->whereType('main_points', 'null')
                ->whereType('read_more', 'null')
                ->whereType('show_breadcrumbs', 'boolean')
                ->where('highlights', ['A highlight'])
                ->where('image_author', null)
                ->has('tags', 1, fn (Assert $tagPayload) => $tagPayload
                    ->where('id', $tag->id)
                    ->where('name', $locale === 'lt' ? 'Studijos' : 'Studies')
                    ->where('alias', $tag->alias)
                )
                ->whereType('reading_time', 'integer')
                ->where('reading_time', fn ($minutes) => $minutes >= 1)
                ->where('image', null)
                ->where('image_media', null)
                ->whereType('tenant', 'string')
                ->where('tenant', $tenant->shortname)
            )
            ->has('relatedArticles', 0)
        );
})->with(['lt', 'en']);

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

test('related articles have the public card contract in each locale', function (string $locale): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create(['lang' => $locale]);
    $related = News::factory()->for($tenant)->create([
        'lang' => $locale,
        'short' => 'Related excerpt',
        'draft' => false,
        'publish_time' => '2026-01-01 12:00:00',
    ]);

    $this->get(newsArticleUrl($news))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('relatedArticles', 1, fn (Assert $item) => $item
                ->where('id', $related->id)
                ->whereType('title', 'string')
                ->whereType('short', 'string')
                ->where('short', 'Related excerpt')
                ->where('lang', $locale)
                ->where('image', null)
                ->where('image_media', null)
                ->whereType('permalink', 'string')
                ->whereType('publish_time', 'string')
                ->whereType('url', 'string')
                ->where('url', newsArticleUrl($related))
            )
        );
})->with(['lt', 'en']);

test('stored null highlights retain their empty array payload', function (string $locale): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create([
        'lang' => $locale,
        'highlights' => null,
    ]);

    $this->get(newsArticleUrl($news))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->where('article.highlights', [])
        );
})->with(['lt', 'en']);

test('article and related article images carry the image data contract', function (string $locale): void {
    Storage::fake('spatieMediaLibrary');
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create(['lang' => $locale]);
    $related = News::factory()->for($tenant)->create([
        'lang' => $locale,
        'publish_time' => now()->subDay(),
    ]);
    foreach ([$news, $related] as $article) {
        $article->addMedia(UploadedFile::fake()->image('article.jpg'))
            ->withCustomProperties(['author' => 'Photographer'])
            ->toMediaCollection('image');
    }

    $imageContract = fn (Assert $image) => $image
        ->whereType('id', 'integer|null')
        ->whereType('url', 'string')
        ->whereType('thumb', 'string')
        ->whereType('srcset', 'string|null')
        ->whereType('width', 'integer|null')
        ->whereType('height', 'integer|null')
        ->whereType('focal_point', 'string|null')
        ->whereType('alt', 'string|null')
        ->where('author', 'Photographer');

    $this->get(newsArticleUrl($news))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->whereType('article.image', 'string')
            ->where('article.image_author', 'Photographer')
            ->has('article.image_media', $imageContract)
            ->whereType('relatedArticles.0.image', 'string')
            ->has('relatedArticles.0.image_media', $imageContract)
        );
})->with(['lt', 'en']);

test('a draft article is not reachable by its link', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($tenant)->create(['lang' => 'lt', 'draft' => true]);

    $this->get(newsArticleUrl($news))->assertNotFound();
});

test('the language switch does not point at a draft translation', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $draftTranslation = News::factory()->for($tenant)->create(['lang' => 'en', 'draft' => true]);
    $news = News::factory()->for($tenant)->create(['lang' => 'lt', 'other_lang_id' => $draftTranslation->id]);

    $this->get(newsArticleUrl($news))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->where('otherLangURL', null)
        );
});

test('returning published news to draft blocks its current and retired URLs', function (): void {
    $tenant = Tenant::main();
    $news = News::factory()->for($tenant)->create([
        'lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(),
    ]);
    $oldUrl = newsArticleUrl($news);
    $this->get($oldUrl)->assertOk();
    $news->update(['permalink' => 'changed-news-permalink']);
    $currentUrl = newsArticleUrl($news);
    $this->get($oldUrl)->assertRedirect($news->publicUrl());
    $news->update(['draft' => true]);
    $this->get($currentUrl)->assertNotFound();
    $this->get($oldUrl)->assertNotFound();
});
