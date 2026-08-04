<?php

use App\Feed\FeedHtml;
use App\Models\Category;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tiptap\Editor;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
        ]
    );

    $this->category = Category::factory()->create();
});

describe('feed rendering', function (): void {
    it('returns RSS 2.0 XML', function (): void {
        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'feed-render-test',
            'title' => 'Feed Render Test',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDay(),
        ]);

        $response = $this->get('/feed');

        expect($response->status())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toContain('xml')
            ->and($response->getContent())
            ->toContain('<rss')
            ->toContain('xmlns:content="http://purl.org/rss/1.0/modules/content/"')
            ->toContain('xmlns:media="http://search.yahoo.com/mrss/"')
            ->toContain('<channel>')
            ->toContain('Feed Render Test');
    });

    it('includes the full article body in content:encoded', function (): void {
        $news = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'full-body-test',
            'title' => 'Full Body Test',
            'short' => 'Short excerpt text',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDay(),
        ]);

        ContentPart::factory()->create([
            'content_id' => $news->content_id,
            'type' => 'tiptap',
            'json_content' => (new Editor)->setContent('<p>UniqueFeedBodyMarker</p>')->getDocument(),
        ]);

        $content = $this->get('/feed')->getContent();

        expect($content)
            ->toContain('<content:encoded>')
            ->toContain('UniqueFeedBodyMarker')
            ->toContain('Short excerpt text');
    });

    it('emits enclosure, media and guid for items with a cover image', function (): void {
        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'enclosure-test',
            'title' => 'Enclosure Test',
            'image' => '/images/placeholders/foto1.jpg',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDay(),
        ]);

        $content = $this->get('/feed')->getContent();

        expect($content)
            ->toContain('<enclosure')
            ->toContain('url="http')
            ->toContain('type="image/')
            ->toContain('<media:content')
            ->toContain('<media:thumbnail')
            ->toContain('<guid isPermaLink="true">');
    });

    it('uses the cover image as an absolute URL', function (): void {
        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'absolute-url-test',
            'title' => 'Absolute URL Test',
            'image' => '/images/placeholders/foto2.jpg',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDay(),
        ]);

        $content = $this->get('/feed')->getContent();

        expect($content)->toContain('src="http')
            ->not->toContain('src="/images/');
    });

    it('lists tags as categories and uses the tenant info email as author', function (): void {
        $tag = Tag::factory()->create(['name' => ['lt' => 'Senatas', 'en' => 'Senate']]);

        $news = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'tags-author-test',
            'title' => 'Tags Author Test',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDay(),
        ]);
        $news->tags()->attach($tag->id);

        $content = $this->get('/feed')->getContent();

        expect($content)
            ->toContain('<category>Senatas</category>')
            ->toContain('info@vusa')
            ->toContain('VU SA');
    });

    it('emits an alternate-language link for translated articles', function (): void {
        $lt = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'lang-pair-lt',
            'title' => 'LT version',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDay(),
        ]);

        $en = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'lang-pair-en',
            'title' => 'EN version',
            'draft' => false,
            'lang' => 'en',
            'publish_time' => now()->subDay(),
            'other_lang_id' => $lt->id,
        ]);

        // Back-reference so the LT article exposes the EN alternate.
        $lt->update(['other_lang_id' => $en->id]);

        $content = $this->get('/feed')->getContent();

        expect($content)
            ->toContain('hreflang="en"')
            ->toContain('lang-pair-en');
    });

    it('excludes draft news', function (): void {
        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'permalink' => 'draft-hidden',
            'title' => 'Draft Should Be Hidden',
            'draft' => true,
            'lang' => 'lt',
            'publish_time' => now()->subDay(),
        ]);

        $content = $this->get('/feed')->getContent();

        expect($content)->not->toContain('Draft Should Be Hidden');
    });
});

describe('FeedHtml absolutization', function (): void {
    it('rewrites root-relative src and href to absolute URLs', function (): void {
        $html = '<img src="/uploads/photo.jpg"><a href="/naujiena/foo">link</a>';

        $absolutized = FeedHtml::absolutize($html);

        expect($absolutized)
            ->toContain('src="http')
            ->not->toContain('src="/uploads/photo.jpg"')
            ->toContain('href="http')
            ->not->toContain('href="/naujiena/foo"');
    });

    it('leaves absolute URLs untouched', function (): void {
        $html = '<img src="https://example.com/photo.jpg">';

        expect(FeedHtml::absolutize($html))->toContain('https://example.com/photo.jpg');
    });
});
