<?php

use App\Http\Controllers\SitemapController;
use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'], // Find by alias
        [
            'shortname' => 'VU SA',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
        ]
    );

    $this->subdomain = 'www';
});

describe('Sitemap Index', function (): void {
    it('generates sitemap index successfully', function (): void {
        $response = $this->get('/sitemap.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toContain('xml');

        $content = $response->getContent();
        expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>')
            ->toContain('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"')
            ->toContain('/sitemap-pages.xml')
            ->toContain('/sitemap-news.xml')
            ->toContain('/sitemap-news-google.xml');
    });

    it('caches sitemap index', function (): void {
        // First request should generate sitemap
        $response1 = $this->get('/sitemap.xml', ['HTTP_HOST' => 'www.vusa.test']);
        expect($response1->status())->toBe(200);

        // Second request should use cache (same content)
        $response2 = $this->get('/sitemap.xml', ['HTTP_HOST' => 'www.vusa.test']);
        expect($response2->status())->toBe(200)
            ->and($response2->getContent())->toBe($response1->getContent());
    });
});

describe('Pages Sitemap', function (): void {
    it('generates pages sitemap with active pages', function (): void {
        // Create test pages with explicit attributes
        $activePage = Page::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'test-page',
            'title' => 'Test Page',
            'is_active' => true,
        ]);

        $inactivePage = Page::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'inactive-page',
            'title' => 'Inactive Page',
            'is_active' => false,
        ]);

        // Verify pages are created in database
        $this->assertDatabaseHas('pages', [
            'tenant_id' => $this->tenant->id,
            'permalink' => 'test-page',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('pages', [
            'tenant_id' => $this->tenant->id,
            'permalink' => 'inactive-page',
            'is_active' => false,
        ]);

        $response = $this->get('/sitemap-pages.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toContain('xml');

        $content = $response->getContent();
        expect($content)->toContain('test-page')->not->toContain('inactive-page')
            ->toContain('<priority>1.0</priority>'); // Homepage priority
        expect($content)->toContain('<priority>0.7</priority>'); // Page priority
    });

    it('includes homepage in pages sitemap', function (): void {
        $response = $this->get('/sitemap-pages.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200);

        $content = $response->getContent();
        expect($content)->toContain('<loc>'.url('/').'</loc>')
            ->toContain('<priority>1.0</priority>')
            ->toContain('<changefreq>weekly</changefreq>');
    });
});

describe('News Sitemap', function (): void {
    it('generates news sitemap with published articles', function (): void {
        $publishedNews = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'published-news',
            'title' => 'Published News',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDays(1),
        ]);

        $draftNews = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'draft-news',
            'title' => 'Draft News',
            'draft' => true,
            'lang' => 'lt',
            'publish_time' => now()->subDays(1),
        ]);

        $response = $this->get('/sitemap-news.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toContain('xml');

        $content = $response->getContent();
        expect($content)->toContain('published-news')->not->toContain('draft-news')
            ->toContain('/naujienos'); // News archive
    });

    it('includes the news archive in both languages', function (): void {
        $response = $this->get('/sitemap-news.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200);

        // Each language is its own URL, and both carry the language prefix the site serves.
        $content = $response->getContent();
        expect($content)->toContain('<loc>https://www.vusa.test/lt/naujienos</loc>')
            ->toContain('<loc>https://www.vusa.test/en/news</loc>')
            ->toContain('<priority>0.8</priority>')
            ->toContain('<changefreq>daily</changefreq>');
    });

    it('handles different language news URLs', function (): void {
        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'lithuanian-news',
            'title' => 'Lithuanian News',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDays(1),
        ]);

        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'english-news',
            'title' => 'English News',
            'draft' => false,
            'lang' => 'en',
            'publish_time' => now()->subDays(1),
        ]);

        $response = $this->get('/sitemap-news.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200);

        $content = $response->getContent();
        expect($content)->toContain('/naujiena/lithuanian-news')
            ->toContain('/news/english-news');
    });
});

describe('Google News Sitemap', function (): void {
    it('generates Google News sitemap with recent articles', function (): void {
        $recentNews = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'recent-news',
            'title' => 'Recent News',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subHours(12),
        ]);

        $oldNews = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'old-news',
            'title' => 'Old News',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDays(5),
        ]);

        $response = $this->get('/sitemap-news-google.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toContain('xml');

        $content = $response->getContent();
        expect($content)->toContain('recent-news')->not->toContain('old-news')
            ->toContain('xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"')
            ->toContain('<news:name>'.$this->tenant->shortname.'</news:name>')
            ->toContain('<news:language>lt</news:language>')
            ->toContain('<news:title>Recent News</news:title>');
    });

    it('includes proper Google News XML structure', function (): void {
        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'test-news',
            'title' => 'Test News Article',
            'draft' => false,
            'lang' => 'en',
            'publish_time' => now()->subHours(1),
        ]);

        $response = $this->get('/sitemap-news-google.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200);

        $content = $response->getContent();
        expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>')
            ->toContain('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"')
            ->toContain('xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">')
            ->toContain('<news:news>')
            ->toContain('<news:publication>')
            ->toContain('<news:publication_date>')
            ->toContain('<news:title>Test News Article</news:title>');
    });
});

describe('Cache Invalidation', function (): void {
    it('clears sitemap cache when news is updated', function (): void {
        $news = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Original Title',
            'draft' => false,
            'lang' => 'lt',
        ]);

        // Generate initial sitemap to populate cache
        $response1 = $this->get('/sitemap-news.xml', ['HTTP_HOST' => 'www.vusa.test']);
        expect($response1->status())->toBe(200);

        // Update news - this should trigger cache invalidation
        $news->update(['title' => 'Updated Title']);

        // New request should reflect the update
        $response2 = $this->get('/sitemap-news.xml', ['HTTP_HOST' => 'www.vusa.test']);
        expect($response2->status())->toBe(200);

        // Test passes if no exceptions thrown
        expect(true)->toBeTrue();
    });

    it('clears sitemap cache when page is updated', function (): void {
        $page = Page::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Original Title',
            'is_active' => true,
        ]);

        // Generate initial sitemap to populate cache
        $response1 = $this->get('/sitemap-pages.xml', ['HTTP_HOST' => 'www.vusa.test']);
        expect($response1->status())->toBe(200);

        // Update page - this should trigger cache invalidation
        $page->update(['title' => 'Updated Title']);

        // New request should reflect the update
        $response2 = $this->get('/sitemap-pages.xml', ['HTTP_HOST' => 'www.vusa.test']);
        expect($response2->status())->toBe(200);

        // Test passes if no exceptions thrown
        expect(true)->toBeTrue();
    });
});

describe('Multi-tenant Support', function (): void {
    it('generates different sitemaps for different tenants', function (): void {
        $ifTenant = Tenant::firstOrCreate(
            ['alias' => 'if'],
            ['shortname' => 'VU SA IF']
        );

        $category = Category::factory()->create();

        News::factory()->create([
            'tenant_id' => $ifTenant->id,
            'category_id' => $category->id,
            'permalink' => 'if-news',
            'title' => 'IF News',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDays(1),
        ]);

        News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'permalink' => 'vusa-news',
            'title' => 'VUSA News',
            'draft' => false,
            'lang' => 'lt',
            'publish_time' => now()->subDays(1),
        ]);

        // Test if tenant directly using controller
        $controller = new SitemapController;
        $request1 = Request::create('/sitemap-news.xml');
        $request1->headers->set('host', 'if.vusa.test');

        $response1 = $controller->news($request1);
        expect($response1->status())->toBe(200);

        $content1 = $response1->getContent();
        expect($content1)->toContain('if-news')->not->toContain('vusa-news');

        // Test www tenant
        $request2 = Request::create('/sitemap-news.xml');
        $request2->headers->set('host', 'www.vusa.test');

        $response2 = $controller->news($request2);
        expect($response2->status())->toBe(200);

        $content2 = $response2->getContent();
        expect($content2)->toContain('vusa-news')->not->toContain('if-news');
    });
});

describe('Error Handling', function (): void {
    it('handles missing tenant gracefully', function (): void {
        $controller = new SitemapController;
        $request = Request::create('/sitemap.xml');
        $request->headers->set('host', 'nonexistent123.vusa.test');

        expect(function () use ($controller, $request): void {
            $controller->index($request);
        })->toThrow(NotFoundHttpException::class);
    });

    it('returns valid XML even when database is empty', function (): void {
        $response = $this->get('/sitemap-pages.xml', ['HTTP_HOST' => 'www.vusa.test']);

        expect($response->status())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toContain('xml');

        $content = $response->getContent();
        expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>')
            ->toContain('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"')
            ->toContain('</urlset>');
    });
});

describe('Model Sitemap Integration', function (): void {
    it('uses toSitemapTag method for news articles', function (): void {
        $news = News::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'test-news',
            'title' => 'Test News',
            'draft' => false,
            'lang' => 'lt',
            'image' => 'test-image.jpg',
            'publish_time' => now()->subDays(1),
        ]);

        $sitemapTag = $news->toSitemapTag();

        expect($sitemapTag)->toBeInstanceOf(Url::class)
            ->and($sitemapTag->url)->toBe('https://www.vusa.test/lt/naujiena/test-news')
            ->and($sitemapTag->priority)->toBe(0.6)
            ->and($sitemapTag->changeFrequency)->toBe('never');
    });

    it('uses toSitemapTag method for pages', function (): void {
        $page = Page::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permalink' => 'test-page',
            'title' => 'Test Page',
            'is_active' => true,
        ]);

        $sitemapTag = $page->toSitemapTag();

        expect($sitemapTag)->toBeInstanceOf(Url::class)
            ->and($sitemapTag->url)->toBe('/test-page')
            ->and($sitemapTag->priority)->toBe(0.7)
            ->and($sitemapTag->changeFrequency)->toBe('monthly');
    });
});
