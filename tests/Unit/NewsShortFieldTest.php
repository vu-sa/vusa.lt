<?php

use App\Helpers\ContentHelper;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('news uses short field for SEO when available', function () {
    $tenant = Tenant::factory()->create();

    // Create news with both short field and tiptap content
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>This should not be used in SEO</p>')->getDocument(),
    ]);

    $news = News::factory()->create([
        'short' => '<p><strong>This is the priority short description</strong> with HTML formatting.</p>',
        'content_id' => $content->id,
        'tenant_id' => $tenant->id,
    ]);

    $description = ContentHelper::getDescriptionForSeo($news);

    // Should use the short field content (stripped of HTML)
    expect($description)->toContain('This is the priority short description');
    expect($description)->toContain('with HTML formatting');
    expect($description)->not->toContain('This should not be used in SEO');
    expect($description)->not->toContain('<p>')->not->toContain('<strong>');
});

test('news strips HTML tags from short field', function () {
    $tenant = Tenant::factory()->create();
    $content = Content::factory()->create();

    $news = News::factory()->create([
        'short' => '<p>Simple <strong>bold</strong> and <em>italic</em> text with <a href="#">link</a>.</p>',
        'content_id' => $content->id,
        'tenant_id' => $tenant->id,
    ]);

    $description = ContentHelper::getDescriptionForSeo($news);

    expect($description)->toBe('Simple bold and italic text with link.');
});

test('news respects character limit', function () {
    $tenant = Tenant::factory()->create();
    $content = Content::factory()->create();

    $longText = str_repeat('This is a very long description that should be truncated. ', 10);

    $news = News::factory()->create([
        'short' => "<p>{$longText}</p>",
        'content_id' => $content->id,
        'tenant_id' => $tenant->id,
    ]);

    $description = ContentHelper::getDescriptionForSeo($news, 50);

    expect(strlen($description))->toBeLessThanOrEqual(53); // 50 + "..." = 53
    expect($description)->toEndWith('...');
});
