<?php

use App\Models\News;
use App\Models\Page;
use App\Models\PublicUrl;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Mirrors CalendarCanonicalUrlTest — News and Page share the same reactive-legacy-write
 * algorithm (News/Page::booted()), but had no direct coverage of the create → change → redirect
 * flow before this.
 */
test('changing a news permalink demotes the old one to a legacy redirect, without touching public_urls before that', function (): void {
    $tenant = Tenant::main();
    $news = News::factory()->for($tenant)->create([
        'permalink' => 'pirma-naujiena',
        'lang' => 'lt',
        'draft' => false,
        'publish_time' => now()->subDay(),
    ]);

    // Nothing is written just from creating the article — there's no "old" url yet.
    expect(PublicUrl::query()->where('urlable_type', $news->getMorphClass())->where('urlable_id', $news->id)->count())->toBe(0);

    $oldUrl = $news->publicUrl();

    $news->update(['permalink' => 'nauja-naujiena']);
    $news->refresh();

    $newUrl = $news->publicUrl();

    expect($newUrl)->not()->toBe($oldUrl);

    $this->get($oldUrl)->assertStatus(301)->assertRedirect($newUrl);
    $this->get($newUrl)->assertStatus(200);
});

test('changing a page permalink demotes the old one to a legacy redirect, without touching public_urls before that', function (): void {
    $tenant = Tenant::main();
    $page = Page::factory()->for($tenant)->create([
        'permalink' => 'pirmas-puslapis',
        'lang' => 'lt',
        'is_active' => true,
    ]);

    expect(PublicUrl::query()->where('urlable_type', $page->getMorphClass())->where('urlable_id', $page->id)->count())->toBe(0);

    $oldUrl = $page->publicUrl();

    $page->update(['permalink' => 'naujas-puslapis']);
    $page->refresh();

    $newUrl = $page->publicUrl();

    expect($newUrl)->not()->toBe($oldUrl);

    $this->get($oldUrl)->assertStatus(301)->assertRedirect($newUrl);
    $this->get($newUrl)->assertStatus(200);
});
