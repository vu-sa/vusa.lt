<?php

use App\Helpers\ContentHelper;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create([
        'alias' => 'test',
        'shortname' => 'Test Tenant',
    ]);
});

test('ContentHelper safely handles null content', function () {
    $page = new Page;
    $page->content = null;

    $result = ContentHelper::getFirstTiptapElement($page->content);
    expect($result)->toBeNull();

    $description = ContentHelper::getDescriptionForSeo($page);
    expect($description)->toBeNull();
});

test('ContentHelper finds tiptap content when it exists', function () {
    $content = Content::factory()->create();
    $part = ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>Test content for SEO</p>')->getDocument(),
    ]);

    $page = new Page;
    $page->content = $content;

    $result = ContentHelper::getFirstTiptapElement($page->content);
    expect($result)->not->toBeNull();
    expect($result->type)->toBe('tiptap');

    $description = ContentHelper::getDescriptionForSeo($page);
    expect($description)->toContain('Test content for SEO');
});

test('ContentHelper prioritizes news short field over tiptap content', function () {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>Tiptap content</p>')->getDocument(),
    ]);

    $news = News::factory()->create([
        'short' => '<p>This is the short description</p>',
        'content_id' => $content->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $description = ContentHelper::getDescriptionForSeo($news);
    expect($description)->toContain('This is the short description');
    expect($description)->not->toContain('Tiptap content');
});

test('ContentHelper falls back to tiptap when news short is empty', function () {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>Fallback tiptap content</p>')->getDocument(),
    ]);

    $news = News::factory()->create([
        'short' => '', // Empty short field
        'content_id' => $content->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $description = ContentHelper::getDescriptionForSeo($news);
    expect($description)->toContain('Fallback tiptap content');
});
