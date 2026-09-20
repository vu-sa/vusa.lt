<?php

use App\Models\Calendar;
use App\Models\News;
use App\Models\Page;
use App\Models\Tag;
use App\Models\Tenant;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
});

test('a topic page 404s for a tag not flagged as a topic', function (): void {
    $tag = Tag::factory()->create();

    $this->get(route('topic', ['subdomain' => 'www', 'lang' => 'lt', 'tag' => $tag->alias]))
        ->assertNotFound();
});

test('a topic page aggregates published news, active pages and upcoming events', function (): void {
    $tag = Tag::factory()->topic()->create();

    $news = News::factory()->create([
        'tenant_id' => $this->tenant->id,
        'lang' => 'lt',
        'draft' => false,
        'publish_time' => now()->subDay(),
    ]);
    $tag->news()->attach($news);

    // A draft article carrying the same tag must not leak into the public page.
    $draftNews = News::factory()->create([
        'tenant_id' => $this->tenant->id,
        'lang' => 'lt',
        'draft' => true,
    ]);
    $tag->news()->attach($draftNews);

    $page = Page::factory()->create([
        'tenant_id' => $this->tenant->id,
        'lang' => 'lt',
        'is_active' => true,
    ]);
    $tag->pages()->attach($page);

    $inactivePage = Page::factory()->create([
        'tenant_id' => $this->tenant->id,
        'lang' => 'lt',
        'is_active' => false,
    ]);
    $tag->pages()->attach($inactivePage);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->addWeek(),
    ]);
    $tag->calendars()->attach($event);

    $pastEvent = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->subMonth(),
    ]);
    $tag->calendars()->attach($pastEvent);

    $this->get(route('topic', ['subdomain' => 'www', 'lang' => 'lt', 'tag' => $tag->alias]))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Public/TopicPage')
            ->where('topic.id', $tag->id)
            ->has('news', 1)
            ->where('news.0.id', $news->id)
            ->has('pages', 1)
            ->where('pages.0.id', $page->id)
            ->has('events', 1)
            ->where('events.0.id', $event->id)
        );
});

test('a topic page shares the other language URL with the tag alias', function (): void {
    $tag = Tag::factory()->topic()->create(['alias' => 'stipendijos']);

    $this->get(route('topic', ['subdomain' => 'www', 'lang' => 'lt', 'tag' => $tag->alias]))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Public/TopicPage')
            ->where('otherLangURL', LocalizedRouteSlugs::route('topic', ['tag' => $tag->alias], 'en'))
        );

    $this->get(LocalizedRouteSlugs::route('topic', ['tag' => $tag->alias], 'en'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Public/TopicPage')
            ->where('otherLangURL', LocalizedRouteSlugs::route('topic', ['tag' => $tag->alias], 'lt'))
        );
});
