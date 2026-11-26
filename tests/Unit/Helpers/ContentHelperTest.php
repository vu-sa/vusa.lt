<?php

use App\Helpers\ContentHelper;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tiptap\Editor;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
});

test('safely handles null content', function (): void {
    $page = new Page;
    $page->content = null;

    expect(ContentHelper::getFirstTiptapElement($page->content))->toBeNull()
        ->and(ContentHelper::getDescriptionForSeo($page))->toBeNull();
});

test('finds tiptap content when it exists', function (): void {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new Editor)->setContent('<p>Test content for SEO</p>')->getDocument(),
    ]);

    $page = new Page;
    $page->content = $content;

    $result = ContentHelper::getFirstTiptapElement($page->content);

    expect($result)->not->toBeNull()
        ->and($result->type)->toBe('tiptap')
        ->and(ContentHelper::getDescriptionForSeo($page))->toContain('Test content for SEO');
});

test('prioritizes news short field over tiptap content and strips HTML tags', function (): void {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new Editor)->setContent('<p>This should not be used in SEO</p>')->getDocument(),
    ]);

    $news = News::factory()->create([
        'short' => '<p><strong>This is the priority short description</strong> with HTML formatting.</p>',
        'content_id' => $content->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $description = ContentHelper::getDescriptionForSeo($news);

    expect($description)
        ->toContain('This is the priority short description')
        ->toContain('with HTML formatting')
        ->not->toContain('This should not be used in SEO')
        ->not->toContain('<p>')
        ->not->toContain('<strong>');
});

test('falls back to tiptap when news short is empty', function (): void {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new Editor)->setContent('<p>Fallback tiptap content</p>')->getDocument(),
    ]);

    $news = News::factory()->create([
        'short' => '',
        'content_id' => $content->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect(ContentHelper::getDescriptionForSeo($news))->toContain('Fallback tiptap content');
});

test('strips HTML tags from news short field', function (): void {
    $content = Content::factory()->create();
    $news = News::factory()->create([
        'short' => '<p>Simple <strong>bold</strong> and <em>italic</em> text with <a href="#">link</a>.</p>',
        'content_id' => $content->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect(ContentHelper::getDescriptionForSeo($news))
        ->toBe('Simple bold and italic text with link.');
});

test('respects character limit when truncating news short field', function (): void {
    $content = Content::factory()->create();
    $longText = str_repeat('This is a very long description that should be truncated. ', 10);

    $news = News::factory()->create([
        'short' => "<p>{$longText}</p>",
        'content_id' => $content->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $description = ContentHelper::getDescriptionForSeo($news, 50);

    expect(strlen($description))->toBeLessThanOrEqual(53)
        ->and($description)->toEndWith('...');
});
