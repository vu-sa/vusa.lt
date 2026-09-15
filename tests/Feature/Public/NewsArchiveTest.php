<?php

use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
});

/**
 * `?tag=` is read two different ways depending on where: the initial SSR list here
 * resolves it against the tag, but the page hydrates to Typesense within moments
 * (useNewsSearch's `tag_names:=[...]` filter, which matches by translated name, not
 * alias) — so both forms have to filter to the same result for the two to agree.
 */
test('the news archive filters by tag alias', function (): void {
    $tag = Tag::factory()->create(['alias' => 'akademine-informacija', 'name' => ['lt' => 'Akademinė informacija', 'en' => 'Academic information']]);
    $matching = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay()]);
    $matching->tags()->attach($tag);
    $other = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay()]);

    $this->get(route('newsArchive', ['subdomain' => 'www', 'lang' => 'lt', 'tag' => 'akademine-informacija']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('news.data', 1)
            ->where('news.data.0.id', $matching->id)
            ->where('currentTag.id', $tag->id)
        );
});

test('the news archive filters by the tag\'s translated name', function (): void {
    $tag = Tag::factory()->create(['alias' => 'akademine-informacija', 'name' => ['lt' => 'Akademinė informacija', 'en' => 'Academic information']]);
    $matching = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay()]);
    $matching->tags()->attach($tag);
    $other = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay()]);

    $this->get(route('newsArchive', ['subdomain' => 'www', 'lang' => 'lt', 'tag' => 'Akademinė informacija']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('news.data', 1)
            ->where('news.data.0.id', $matching->id)
            ->where('currentTag.id', $tag->id)
        );
});
