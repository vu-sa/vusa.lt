<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\Type;
use App\Settings\MeetingSettings;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tiptap\Editor;

pest()->use(RefreshDatabase::class);

/**
 * Create an Institution whose type is allowed by MeetingSettings, and attach it to a
 * freshly created Meeting — the minimum setup ContactController::showMeeting() needs to
 * not 404 (it gates on the institution's type being in the public-meeting allowlist).
 */
function makePublicMeeting(Tenant $tenant, array $institutionAttributes = [], array $meetingAttributes = []): Meeting
{
    $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);

    $settings = app(MeetingSettings::class);
    $settings->public_meeting_institution_type_ids = [$type->id];
    $settings->save();

    $institution = Institution::factory()->create([
        'tenant_id' => $tenant->id,
        ...$institutionAttributes,
    ]);
    $institution->types()->attach($type->id);

    $meeting = Meeting::factory()->create($meetingAttributes);
    $meeting->institutions()->attach($institution->id);

    return $meeting;
}

beforeEach(function (): void {
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

describe('Canonical URL generation', function (): void {
    it('generates canonical URL for home page with correct subdomain', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('head')
        );

        // The canonical URL should use www subdomain for main tenant content
        $html = $response->getContent();
        expect($html)->toContain('rel="canonical"');
    });

    it('generates canonical URL for padalinys home page with correct subdomain', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'mif', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
        );
    });

    it('includes canonical URL pointing at the content owner subdomain for news article', function (): void {
        $news = News::factory()->create([
            'tenant_id' => $this->mifTenant->id,
            'title' => 'Test MIF News',
            'permalink' => 'test-mif-news',
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        // News is only reachable from its owning tenant's subdomain — but the canonical URL
        // is still built from the content owner explicitly (getCanonicalUrl(contentTenant: …)),
        // not derived from the current request, so this still exercises that code path.
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
            ->has('head')
        );

        $html = $response->getContent();
        expect($html)->toContain('rel="canonical"')
            ->toMatch('/rel="canonical"[^>]*href="https:\/\/mif\./');
    });

    it('includes canonical URL in head metadata for page', function (): void {
        $content = Content::factory()->create();
        ContentPart::factory()->create([
            'content_id' => $content->id,
            'type' => 'tiptap',
            'json_content' => (new Editor)->setContent('<p>Test content</p>')->getDocument(),
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
            ->has('head')
        );
    });
});

describe('Inertia head adoption', function (): void {
    it('stamps page-managed head elements with data-inertia for SPA adoption', function (): void {
        // Regression test: AppServiceProvider used to stamp a stale `inertia=""` attribute
        // (Inertia v2). Laravel Head must stamp the current `data-inertia="..."` attribute
        // instead, or the SPA can no longer adopt/replace these elements on navigation.
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $html = $response->getContent();

        expect($html)->toContain('data-inertia=')->not->toMatch('/<title[^>]*\sinertia=""/');
    });
});

describe('Hreflang tags', function (): void {
    it('renders hreflang alternate links for bilingual content', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $html = $response->getContent();

        expect($html)->toContain('hreflang="x-default"')
            ->toContain('hreflang="lt"');
    });

    it('includes x-default hreflang for Lithuanian content', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        expect($response->getContent())->toContain('hreflang="x-default"');
    });

    it('includes lt and en hreflang tags', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $html = $response->getContent();

        expect($html)->toContain('hreflang="lt"')
            ->toContain('hreflang="en"');
    });
});

describe('Pagination SEO metadata', function (): void {
    it('renders rel=next on the first page of a paginated archive', function (): void {
        // NewsController::newsArchive() paginates at 15 — 16 is the smallest count that
        // produces a second page.
        News::factory()->count(16)->create([
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
        $response->assertInertia(fn (Assert $page) => $page->component('Public/NewsArchive'));

        $html = $response->getContent();
        expect($html)->toContain('rel="next"')->not->toContain('rel="prev"');
    });

    it('renders rel=prev when on page 2 of a paginated archive', function (): void {
        // NewsController::newsArchive() paginates at 15 — 16 is the smallest count that
        // produces a second page.
        News::factory()->count(16)->create([
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
        expect($response->getContent())->toContain('rel="prev"');
    });
});

describe('OtherLangURL sharing', function (): void {
    it('shares otherLangURL for home page', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('otherLangURL')
        );
    });

    it('shares null otherLangURL when no translation exists', function (): void {
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

    it('shares correct otherLangURL when translation exists', function (): void {
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

describe('Tenant subdomain handling', function (): void {
    it('uses www subdomain for vusa (main) tenant', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tenant.subdomain', 'www')
            ->where('tenant.alias', 'vusa')
        );
    });

    it('uses alias as subdomain for padalinys tenant', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'mif', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tenant.subdomain', 'mif')
            ->where('tenant.alias', 'mif')
        );
    });
});

describe('SEO structured data', function (): void {
    it('shares organization schema', function (): void {
        $response = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('schemas')
        );
    });
});

describe('Robots directive override', function (): void {
    it('renders noindex, nofollow for a meeting page', function (): void {
        $meeting = makePublicMeeting($this->mainTenant);

        $response = $this->get(route('publicMeetings.show', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'meeting' => $meeting->id,
        ]));

        $response->assertStatus(200);
        expect($response->getContent())->toContain('noindex, nofollow');
    });
});

describe('Title suffixes', function (): void {
    it('suffixes a news article title with the content-owning tenant, not the accessing one', function (): void {
        $news = News::factory()->create([
            'tenant_id' => $this->mifTenant->id,
            'title' => 'MIF specific news',
            'permalink' => 'mif-title-suffix-news',
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $response = $this->get(route('news', [
            'subdomain' => 'mif',
            'lang' => 'lt',
            'news' => $news->permalink,
            'newsString' => 'naujiena',
        ]));

        $response->assertStatus(200);
        expect($response->getContent())->toMatch('/<title[^>]*>MIF specific news - VU SA MIF</');
    });

    it('gives a previously suffix-less page a tenant suffix', function (): void {
        $response = $this->get(route('documents', ['subdomain' => 'www', 'lang' => 'lt']));

        $response->assertStatus(200);
        expect($response->getContent())->toMatch('/<title[^>]*>[^<]* - VU SA</');
    });

    it('renders the tenant name exactly once in a news archive title', function (): void {
        News::factory()->count(3)->create([
            'tenant_id' => $this->mifTenant->id,
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $response = $this->get(route('newsArchive', [
            'subdomain' => 'mif',
            'lang' => 'lt',
            'newsString' => 'naujienos',
        ]));

        $response->assertStatus(200);
        preg_match('/<title[^>]*>([^<]*)</', $response->getContent(), $matches);
        $title = $matches[1] ?? '';

        expect($title)->not->toBeEmpty()
            ->and(substr_count($title, 'VU SA MIF'))->toBe(1);
    });

    it('suffixes a meeting title with the institution name rather than the tenant', function (): void {
        $meeting = makePublicMeeting(
            $this->mainTenant,
            institutionAttributes: ['name' => 'Test Institution For Meeting Title'],
            meetingAttributes: ['start_time' => '2026-05-14 10:00:00'],
        );

        $response = $this->get(route('publicMeetings.show', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'meeting' => $meeting->id,
        ]));

        $response->assertStatus(200);
        // The head title is generated per locale by MeetingTitle, not read from the
        // stored (always-Lithuanian) column.
        expect($response->getContent())
            ->toMatch('/<title[^>]*>2026 gegužės 14 d\. 10\.00 val\. posėdis - Test Institution For Meeting Title</');
    });
});
