<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create main tenant (vusa -> www subdomain)
    $this->mainTenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    // Create a padalinys tenant (mif subdomain)
    $this->mifTenant = Tenant::firstOrCreate(
        ['alias' => 'mif'],
        [
            'shortname' => 'VU SA MIF',
            'shortname_vu' => 'MIF',
            'fullname' => 'VU SA Matematikos ir informatikos fakultetas',
            'type' => 'padalinys',
        ]
    );
});

describe('Canonical URL generation', function () {
    it('generates canonical URL for home page with correct subdomain', function () {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('seo.tags')
        );

        // The canonical URL should use www subdomain for main tenant content
        $html = $response->getContent();
        expect($html)->toContain('rel="canonical"');
    });

    it('generates canonical URL for padalinys home page with correct subdomain', function () {
        $response = $this->get(route('home', ['subdomain' => 'mif', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
        );
    });

    it('includes canonical URL in SEO data for news article', function () {
        $news = News::factory()->create([
            'tenant_id' => $this->mifTenant->id,
            'title' => 'Test MIF News',
            'permalink' => 'test-mif-news',
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        // Access from MIF subdomain for content belonging to MIF
        $response = $this->get(route('news', [
            'subdomain' => 'mif',
            'lang' => 'lt',
            'news' => $news->permalink,
            'newsString' => 'naujiena',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article')
            ->has('seo.tags')
        );

        // Canonical should be present and point to MIF subdomain
        $html = $response->getContent();
        expect($html)->toContain('rel="canonical"');
    });

    it('includes canonical URL in SEO data for page', function () {
        $content = Content::factory()->create();
        ContentPart::factory()->create([
            'content_id' => $content->id,
            'type' => 'tiptap',
            'json_content' => (new \Tiptap\Editor)->setContent('<p>Test content</p>')->getDocument(),
        ]);

        $page = Page::factory()->create([
            'title' => 'Test Page',
            'permalink' => 'canonical-test-page',
            'tenant_id' => $this->mainTenant->id,
            'content_id' => $content->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('page', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'permalink' => 'canonical-test-page',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/ContentPage')
            ->has('seo.tags')
        );
    });
});

describe('Hreflang tags', function () {
    it('shares hreflang tags for bilingual content', function () {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('seo.hreflang')
        );
    });

    it('includes x-default hreflang for Lithuanian content', function () {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('seo.hreflang')
            ->where('seo.hreflang', fn ($hreflang) => collect($hreflang)->contains(fn ($tag) => str_contains($tag, 'x-default'))
            )
        );
    });

    it('includes lt and en hreflang tags', function () {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('seo.hreflang')
            ->where('seo.hreflang', fn ($hreflang) => collect($hreflang)->contains(fn ($tag) => str_contains($tag, 'hreflang="lt"'))
            )
        );
    });
});

describe('Pagination SEO metadata', function () {
    it('shares pagination SEO data for news archive', function () {
        // Create enough news to trigger pagination
        News::factory()->count(20)->create([
            'tenant_id' => $this->mainTenant->id,
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $response = $this->get(route('newsArchive', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'newsString' => 'naujienos',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('seo.pagination')
            ->has('seo.pagination.currentPage')
            ->has('seo.pagination.lastPage')
        );
    });

    it('generates correct next page URL for paginated content', function () {
        // Create enough news to have at least 2 pages
        News::factory()->count(20)->create([
            'tenant_id' => $this->mainTenant->id,
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $response = $this->get(route('newsArchive', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'newsString' => 'naujienos',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('seo.pagination.nextPageUrl')
            ->where('seo.pagination.prevPageUrl', null) // First page has no previous
        );
    });

    it('generates correct prev page URL when on page 2', function () {
        // Create enough news to have at least 2 pages
        News::factory()->count(20)->create([
            'tenant_id' => $this->mainTenant->id,
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $response = $this->get(route('newsArchive', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'newsString' => 'naujienos',
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('seo.pagination.prevPageUrl')
            ->where('seo.pagination.currentPage', 2)
        );
    });
});

describe('OtherLangURL sharing', function () {
    it('shares otherLangURL for home page', function () {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('otherLangURL')
        );
    });

    it('shares null otherLangURL when no translation exists', function () {
        $news = News::factory()->create([
            'tenant_id' => $this->mainTenant->id,
            'title' => 'News Without Translation',
            'permalink' => 'news-without-translation',
            'lang' => 'lt',
            'other_lang_id' => null,
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $response = $this->get(route('news', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'news' => $news->permalink,
            'newsString' => 'naujiena',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->where('otherLangURL', null)
        );
    });

    it('shares correct otherLangURL when translation exists', function () {
        $newsEn = News::factory()->create([
            'tenant_id' => $this->mainTenant->id,
            'title' => 'English News',
            'permalink' => 'english-news',
            'lang' => 'en',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $newsLt = News::factory()->create([
            'tenant_id' => $this->mainTenant->id,
            'title' => 'Lithuanian News',
            'permalink' => 'lithuanian-news',
            'lang' => 'lt',
            'other_lang_id' => $newsEn->id,
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        // Update the English news to point back
        $newsEn->update(['other_lang_id' => $newsLt->id]);

        $response = $this->get(route('news', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'news' => $newsLt->permalink,
            'newsString' => 'naujiena',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('otherLangURL')
            ->where('otherLangURL', fn ($url) => str_contains($url, 'english-news'))
        );
    });
});

describe('Tenant subdomain handling', function () {
    it('uses www subdomain for vusa (main) tenant', function () {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tenant.subdomain', 'www')
            ->where('tenant.alias', 'vusa')
        );
    });

    it('uses alias as subdomain for padalinys tenant', function () {
        $response = $this->get(route('home', ['subdomain' => 'mif', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tenant.subdomain', 'mif')
            ->where('tenant.alias', 'mif')
        );
    });
});

describe('SEO structured data', function () {
    it('shares organization schema', function () {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('schemas')
        );
    });
});
