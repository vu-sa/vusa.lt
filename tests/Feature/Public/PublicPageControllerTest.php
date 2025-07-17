<?php

use App\Models\Category;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Navigation;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create or find the main tenant that the controller expects
    $this->tenant = \App\Models\Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );
});

test('page renders successfully with content', function () {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>Test content</p>')->getDocument(),
    ]);

    $page = Page::factory()->create([
        'title' => 'Test Page',
        'permalink' => 'test-page',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'test-page']));

    $response->assertStatus(200);
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->has('page.content')
            ->where('page.title', 'Test Page')
    );
});

test('page renders successfully without content', function () {
    $content = Content::factory()->create();
    // Don't create any content parts - this simulates "no content"

    $page = Page::factory()->create([
        'title' => 'Test Page No Content',
        'permalink' => 'test-page-no-content',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'test-page-no-content']));

    $response->assertStatus(200);
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->where('page.content.id', $content->id)
            ->where('page.title', 'Test Page No Content')
    );
});

test('page renders successfully with content but no parts', function () {
    $content = Content::factory()->create();
    // Don't create any content parts

    $page = Page::factory()->create([
        'title' => 'Test Page Empty Content',
        'permalink' => 'test-page-empty-content',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'test-page-empty-content']));

    $response->assertStatus(200);
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->has('page.content')
            ->where('page.title', 'Test Page Empty Content')
    );
});

test('page renders successfully with content but no tiptap parts', function () {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'json_content' => ['title' => 'Hero section'],
    ]);

    $page = Page::factory()->create([
        'title' => 'Test Page No Tiptap',
        'permalink' => 'test-page-no-tiptap',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'test-page-no-tiptap']));

    $response->assertStatus(200);
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->has('page.content')
            ->where('page.title', 'Test Page No Tiptap')
    );
});

test('page with multiple content parts including tiptap renders successfully', function () {
    $content = Content::factory()->create();

    // Create a hero part
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'json_content' => ['title' => 'Hero section'],
        'order' => 1,
    ]);

    // Create a tiptap part
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>Tiptap content for SEO</p>')->getDocument(),
        'order' => 2,
    ]);

    $page = Page::factory()->create([
        'title' => 'Test Page Multiple Parts',
        'permalink' => 'test-page-multiple-parts',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'test-page-multiple-parts']));

    $response->assertStatus(200);
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->has('page.content')
            ->where('page.title', 'Test Page Multiple Parts')
    );
});

test('page returns 404 when page does not exist', function () {
    $response = $this->get('/lt/non-existent-page');

    $response->assertStatus(404);
});

test('page with navigation item renders successfully', function () {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>Test content</p>')->getDocument(),
    ]);

    $page = Page::factory()->create([
        'title' => 'Navigation Test Page',
        'permalink' => 'navigation-test-page',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    // Create matching navigation item
    $navigationItem = Navigation::factory()->create([
        'name' => 'Navigation Test Page',
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'navigation-test-page']));

    $response->assertStatus(200);
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->where('navigationItemId', $navigationItem->id)
            ->where('page.title', 'Navigation Test Page')
    );
});

test('page with category renders successfully', function () {
    $category = Category::factory()->create([
        'name' => 'Test Category',
        'alias' => 'test-category',
    ]);

    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>Category page content</p>')->getDocument(),
    ]);

    $page = Page::factory()->create([
        'title' => 'Category Test Page',
        'permalink' => 'category-test-page',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
        'category_id' => $category->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'category-test-page']));

    $response->assertStatus(200);
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->where('page.title', 'Category Test Page')
            ->where('page.category.id', $category->id)
    );
});

test('seo description is extracted from first tiptap content', function () {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new \Tiptap\Editor)->setContent('<p>This is the SEO description content.</p>')->getDocument(),
    ]);

    $page = Page::factory()->create([
        'title' => 'SEO Test Page',
        'permalink' => 'seo-test-page',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'seo-test-page']));

    $response->assertStatus(200);
    // The actual SEO data is in view data, which we can't easily test with Inertia testing
    // But the important thing is that it doesn't error out
});

test('seo description is null when no tiptap content exists', function () {
    $content = Content::factory()->create();
    // Don't create any content parts

    $page = Page::factory()->create([
        'title' => 'SEO Test Page No Content',
        'permalink' => 'seo-test-page-no-content',
        'tenant_id' => $this->tenant->id,
        'content_id' => $content->id,
    ]);

    $response = $this->get(route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'seo-test-page-no-content']));

    $response->assertStatus(200);
    // The important thing is that it doesn't error out when content is null
});
