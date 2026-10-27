<?php

use App\Actions\Media\AddImageMedia;
use App\Enums\TenantType;
use App\Models\Calendar;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\DutyType;
use App\Models\Form;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\SystemDutyTypes;
use App\Settings\DutyTypeSettings;
use App\Settings\MeetingSettings;
use App\Support\LocalizedRouteSlugs;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use Tiptap\Editor;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake();
});

function seoTenant(string $alias): Tenant
{
    return Tenant::query()->where('alias', $alias)->firstOrFail();
}

/**
 * @return array{title: string, description: string, canonical: string, ogUrl: ?string, ogImage: ?string, siteName: ?string, locale: ?string, alternates: list<string>, jsonLd: list<mixed>}
 */
function parseSeoHead(string $html): array
{
    $head = HTMLDocument::createFromString($html, LIBXML_NOERROR)->head;

    $titles = $head->querySelectorAll('title');
    $descriptions = $head->querySelectorAll('meta[name="description"]');
    $canonicals = $head->querySelectorAll('link[rel="canonical"]');

    expect($titles)->toHaveCount(1)
        ->and($descriptions)->toHaveCount(1, 'meta description is missing')
        ->and($canonicals)->toHaveCount(1);

    $property = fn (string $name): ?string => $head->querySelector("meta[property=\"{$name}\"]")?->getAttribute('content');

    return [
        'title' => trim($titles->item(0)->textContent),
        'description' => (string) $descriptions->item(0)->getAttribute('content'),
        'canonical' => (string) $canonicals->item(0)->getAttribute('href'),
        'ogUrl' => $property('og:url'),
        'ogImage' => $property('og:image'),
        'siteName' => $property('og:site_name'),
        'locale' => $property('og:locale'),
        'alternates' => array_map(fn ($link) => (string) $link->getAttribute('href'), iterator_to_array($head->querySelectorAll('link[rel="alternate"][hreflang]'))),
        'jsonLd' => array_map(fn ($script) => json_decode($script->textContent, true), iterator_to_array(HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('script[type="application/ld+json"]'))),
    ];
}

/**
 * Every string under a link-like key, anywhere in the JSON-LD graph.
 *
 * @return list<string>
 */
function jsonLdUrls(mixed $node): array
{
    if (! is_array($node)) {
        return [];
    }

    $urls = [];

    foreach ($node as $key => $value) {
        if (in_array($key, ['url', 'item', 'mainEntityOfPage'], true) && is_string($value)) {
            $urls[] = $value;
        }

        array_push($urls, ...jsonLdUrls($value));
    }

    return $urls;
}

it('serves a complete head for every public page', function (Closure $arrange): void {
    [$url, $brand] = $arrange();

    $head = parseSeoHead($this->get($url)->assertOk()->getContent());
    $canonical = Uri::of($head['canonical']);

    expect($head['title'])->toEndWith(" - {$brand}")
        ->not->toContain(' | ')
        ->not->toContain('VU SR')
        ->and(substr_count($head['title'], $brand))->toBe(1, "brand repeated in \"{$head['title']}\"")
        ->and(mb_strlen($head['description']))->toBeBetween(50, 160, "description \"{$head['description']}\"")
        ->and($head['description'])->not->toMatch('/<|&[a-z#0-9]+;/i')
        ->and($canonical->scheme())->toBe('https')
        ->and(array_keys($canonical->query()->all()))->each->toBeIn(['page', 'tag'])
        ->and($head['ogUrl'])->toBe($head['canonical'])
        ->and($head['ogImage'])->toStartWith('http')
        ->and($head['siteName'])->toBe($brand)
        ->and($head['locale'])->toBe(app()->getLocale() === 'en' ? 'en_US' : 'lt_LT')
        ->and($head['alternates'])->toContain($head['canonical']);

    foreach ($head['jsonLd'] as $schema) {
        expect($schema)->toBeArray();

        foreach (jsonLdUrls($schema) as $schemaUrl) {
            $uri = Uri::of($schemaUrl);

            expect($uri->scheme())->toBe('https', $schemaUrl)
                ->and(explode('.', (string) $uri->host())[0])->not->toBe('vusa', $schemaUrl)
                ->and($uri->path())->toMatch('#^(/?|(lt|en)(/.*)?)$#', $schemaUrl);
        }
    }
})->with([
    'home on www' => fn () => [route('home', ['subdomain' => 'www', 'lang' => 'lt']), 'VU SA'],
    'home on a unit in English' => fn () => [route('home', ['subdomain' => 'mif', 'lang' => 'en']), seoTenant('mif')->shortname],
    'content page' => function (): array {
        $content = Content::factory()->create();
        ContentPart::factory()->create([
            'content_id' => $content->id,
            'type' => 'tiptap',
            'json_content' => (new Editor)->setContent('<p>Trumpas.</p>')->getDocument(),
        ]);
        Page::factory()->create([
            'permalink' => 'seo-puslapis',
            'tenant_id' => seoTenant('vusa')->id,
            'content_id' => $content->id,
            'is_active' => true,
            'meta_description' => 'Puslapio aprašymas, kurį redaktorius parašė paieškos rezultatams ir šio puslapio įžangai.',
        ]);

        return [route('page', ['subdomain' => 'www', 'lang' => 'lt', 'permalink' => 'seo-puslapis']), 'VU SA'];
    },
    'news article' => function (): array {
        $news = News::factory()->create([
            'tenant_id' => seoTenant('mif')->id,
            'lang' => 'lt',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        return [LocalizedRouteSlugs::route('news', ['subdomain' => 'mif', 'news' => $news->permalink], 'lt'), seoTenant('mif')->shortname];
    },
    'news archive with tracking parameters' => fn () => [
        LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'www', 'utm_source' => 'fb', 'fbclid' => 'x', 'page' => 1], 'lt'),
        'VU SA',
    ],
    'contacts search' => fn () => [LocalizedRouteSlugs::route('contacts', ['subdomain' => 'www'], 'lt'), 'VU SA'],
    'programmes clubs and projects' => function (): array {
        Page::factory()->create([
            'tenant_id' => seoTenant('vusa')->id,
            'permalink' => 'programos-klubai-projektai',
            'is_active' => true,
        ]);

        return [LocalizedRouteSlugs::route('pkp', [], 'lt'), 'VU SA'];
    },
    'institution without a description' => function (): array {
        $institution = Institution::factory()->create([
            'tenant_id' => seoTenant('mif')->id,
            'alias' => 'seo-kolegija',
            'description' => ['lt' => '', 'en' => ''],
            'is_active' => true,
        ]);

        return [$institution->publicUrl('lt'), seoTenant('mif')->shortname];
    },
    'institution by id with an HTML description' => function (): array {
        $institution = Institution::factory()->create([
            'tenant_id' => seoTenant('mif')->id,
            'description' => ['lt' => '<p>Fakulteto studijų kolegija&nbsp;svarsto studijų programų kokybę, tvirtina pakeitimus ir sprendžia studentų prašymus.</p>', 'en' => ''],
            'is_active' => true,
        ]);

        return [LocalizedRouteSlugs::route('contacts.institution', ['subdomain' => 'mif', 'institution' => $institution->id], 'lt'), seoTenant('mif')->shortname];
    },
    'contact category' => function (): array {
        $type = InstitutionType::factory()->create(['slug' => 'seo-kategorija']);

        return [LocalizedRouteSlugs::route('contacts.category', ['subdomain' => 'www', 'type' => $type->slug], 'lt'), 'VU SA'];
    },
    'student representatives' => fn () => [LocalizedRouteSlugs::route('contacts.studentRepresentatives', ['subdomain' => 'mif'], 'lt'), seoTenant('mif')->shortname],
    'coordinators' => function (): array {
        $type = DutyType::firstOrCreate(['id' => 6], ['title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators']]);
        $settings = app(DutyTypeSettings::class);
        $settings->coordinator_type_id = $type->id;
        $settings->save();
        app(SystemDutyTypes::class)->flush();
        Institution::factory()->create(['tenant_id' => seoTenant('mif')->id, 'alias' => 'mif', 'is_active' => true]);

        return [LocalizedRouteSlugs::route('contacts.dutyType', ['subdomain' => 'mif', 'type' => 'koordinatoriai'], 'lt'), 'VU SA MIF'];
    },
    'calendar' => fn () => [LocalizedRouteSlugs::route('calendar.list', [], 'lt'), 'VU SA'],
    'calendar event' => function (): array {
        $event = Calendar::factory()->create(['tenant_id' => seoTenant('mif')->id, 'is_draft' => false]);

        return [$event->publicUrl('lt'), seoTenant('mif')->shortname];
    },
    'documents' => fn () => [LocalizedRouteSlugs::route('documents', [], 'lt'), 'VU SA'],
    'search' => fn () => [LocalizedRouteSlugs::route('search', [], 'lt'), 'VU SA'],
    'meetings' => fn () => [LocalizedRouteSlugs::route('publicMeetings.index', ['subdomain' => 'www'], 'lt'), 'VU SA'],
    'study sets' => fn () => [route('studySets', ['lang' => 'lt']), 'VU SA'],
    'topic' => function (): array {
        $tag = Tag::factory()->topic()->create(['description' => ['lt' => 'Trumpa.', 'en' => 'Short.']]);

        return [LocalizedRouteSlugs::route('topic', ['tag' => $tag->alias], 'lt'), 'VU SA'];
    },
    'goals' => function (): array {
        $tenant = seoTenant('mif');
        $tenant->update(['goals_enabled' => true]);
        $goal = Goal::factory()->public()->create(['tenant_id' => $tenant->id]);

        return [LocalizedRouteSlugs::route('publicGoals.show', ['subdomain' => 'mif', 'goal' => $goal->id], 'lt'), $tenant->shortname];
    },
    'goals overview' => function (): array {
        seoTenant('mif')->update(['goals_enabled' => true]);

        return [LocalizedRouteSlugs::route('publicGoals.index', ['subdomain' => 'mif'], 'en'), 'VU SA MIF'];
    },
    'registration form' => function (): array {
        $form = Form::factory()->create(['publish_time' => now()->subDay(), 'path' => ['lt' => 'seo-registracija', 'en' => 'seo-registration']]);

        return [LocalizedRouteSlugs::route('registrationPage', ['registrationForm' => 'seo-registracija'], 'lt'), 'VU SA'];
    },
]);

it('canonicalises the news archive to the bare URL and keeps the page number', function (): void {
    $archive = fn (array $query) => parseSeoHead($this->get(LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'www', ...$query], 'lt'))->getContent())['canonical'];

    expect($archive(['utm_source' => 'fb', 'page' => 1]))->toBe(LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'www'], 'lt'))
        ->and($archive(['page' => 2, 'gclid' => 'x']))->toBe(LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'www', 'page' => 2], 'lt'));
});

it('describes an institution by its own text, as plain text', function (): void {
    $institution = Institution::factory()->create([
        'tenant_id' => seoTenant('mif')->id,
        'alias' => 'seo-taryba',
        'description' => ['lt' => '<p>Fakulteto taryba&nbsp;svarsto studijų programų kokybę ir tvirtina jų pakeitimus kiekvieną semestrą.</p>', 'en' => ''],
        'is_active' => true,
    ]);

    $head = parseSeoHead($this->get($institution->publicUrl('lt'))->getContent());

    expect($head['description'])->toBe('Fakulteto taryba svarsto studijų programų kokybę ir tvirtina jų pakeitimus kiekvieną semestrą.')
        ->and($head['title'])->toBe("{$institution->name} - VU SA MIF")
        ->and($head['canonical'])->toBe($institution->publicUrl('lt'));
});

it('falls back to a sentence naming the institution when it has no description', function (): void {
    $institution = Institution::factory()->create([
        'tenant_id' => seoTenant('mif')->id,
        'alias' => 'seo-be-aprasymo',
        'description' => ['lt' => '', 'en' => ''],
        'is_active' => true,
    ]);

    expect(parseSeoHead($this->get($institution->publicUrl('lt'))->getContent())['description'])
        ->toStartWith("{$institution->name} – VU SA MIF kontaktai");
});

it('names a content page when its description and text are too short', function (string $locale, string $text): void {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => (new Editor)->setContent('<p>'.$text.'</p>')->getDocument(),
    ]);
    $page = Page::factory()->create([
        'tenant_id' => seoTenant('vusa')->id,
        'content_id' => $content->id,
        'title' => 'Scholarships',
        'lang' => $locale,
        'is_active' => true,
        'meta_description' => '',
    ]);

    $head = parseSeoHead($this->get($page->publicUrl())->assertOk()->getContent());

    expect($head['description'])->toBe(__('seo.page_description', ['page' => 'Scholarships', 'tenant' => 'VU SA'], $locale));
})->with([['lt', ''], ['en', 'Short.']]);

it('names a news article when its excerpt and body are empty', function (): void {
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => ['type' => 'doc', 'content' => []],
    ]);
    $news = News::factory()->create([
        'tenant_id' => seoTenant('mif')->id,
        'content_id' => $content->id,
        'title' => 'Student elections',
        'short' => '',
        'lang' => 'en',
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);

    expect(parseSeoHead($this->get($news->publicUrl())->assertOk()->getContent())['description'])
        ->toBe(__('seo.page_description', ['page' => $news->title, 'tenant' => 'VU SA MIF'], 'en'));
});

it('caps both authored descriptions and generated fallbacks', function (bool $hasDescription): void {
    $institution = Institution::factory()->create([
        'tenant_id' => seoTenant('mif')->id,
        'alias' => 'long-description',
        'name' => ['lt' => str_repeat('Studijų kolegija ', 15), 'en' => 'Study committee'],
        'description' => ['lt' => $hasDescription ? '<p>'.str_repeat('Studijų kokybė ir studentų atstovavimas. ', 10).'</p>' : '', 'en' => ''],
        'is_active' => true,
    ]);

    $head = parseSeoHead($this->get($institution->publicUrl('lt'))->assertOk()->getContent());

    expect(mb_strlen($head['description']))->toBeLessThanOrEqual(160)
        ->and($head['description'])->toEndWith('…')->not->toContain('<p>');
})->with([true, false]);

it('canonicalises PKP institution ids on other hosts to their www alias', function (): void {
    $tenant = Tenant::factory()->create(['type' => TenantType::Pkp]);
    $institution = Institution::factory()->create([
        'tenant_id' => $tenant->id, 'alias' => 'pkp-seo', 'is_active' => true,
    ]);
    $url = LocalizedRouteSlugs::route('contacts.institution', ['subdomain' => 'mif', 'institution' => $institution->id], 'en');
    $head = parseSeoHead($this->get($url)->assertOk()->getContent());

    expect($head['canonical'])->toBe($institution->publicUrl('en'))
        ->and($head['siteName'])->toBe('VU SA')
        ->and($head['alternates'])->toContain($institution->publicUrl('lt'));
});

it('keeps tagged pagination but removes tracking parameters', function (): void {
    $tag = Tag::factory()->create(['alias' => 'seo-tag']);
    $url = LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'mif', 'tag' => $tag->alias, 'page' => 2, 'utm_source' => 'email'], 'lt');
    $head = parseSeoHead($this->get($url)->assertOk()->getContent());

    expect($head['canonical'])->toBe(LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'mif', 'page' => 2, 'tag' => $tag->alias], 'lt'));
    $reordered = LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'mif', 'page' => 2, 'tag' => $tag->alias], 'lt');
    expect(parseSeoHead($this->get($reordered)->assertOk()->getContent())['canonical'])->toBe($head['canonical']);
});

it('identifies the same site root on both language homepages', function (string $locale, string $subdomain): void {
    $head = parseSeoHead($this->get(route('home', ['subdomain' => $subdomain, 'lang' => $locale]))->assertOk()->getContent());
    $website = collect($head['jsonLd'])->firstWhere('@type', 'WebSite');

    expect($website['url'])->toBe(route('home', ['subdomain' => $subdomain, 'lang' => null]))
        ->and($website['alternateName'])->toBe(seoTenant($subdomain === 'www' ? 'vusa' : $subdomain)->shortname);
})->with(['lt', 'en'])->with(['www', 'mif']);

it('uses the published canonical URL in article and event structured data', function (): void {
    $news = News::factory()->create(['tenant_id' => seoTenant('mif')->id, 'lang' => 'en']);
    $event = Calendar::factory()->create(['tenant_id' => seoTenant('vusa')->id]);
    app()->setLocale('en');
    $article = $news->toNewsArticleSchema()->toArray();
    $schema = $event->toEventSchema()->toArray();

    expect($article['url'])->toBe($news->publicUrl())
        ->and($article['mainEntityOfPage'])->toBe($news->publicUrl())
        ->and($article['publisher']['url'])->toBe(route('home', ['subdomain' => 'mif', 'lang' => 'en']))
        ->and($schema['url'])->toBe($event->publicUrl('en'))
        ->and($schema['organizer']['url'])->toBe(route('home', ['subdomain' => 'www', 'lang' => 'en']));
});

it('retains the meeting page noindex override alongside a complete description', function (): void {
    $type = InstitutionType::factory()->create();
    $settings = app(MeetingSettings::class);
    $settings->public_meeting_institution_type_ids = [$type->id];
    $settings->save();
    $institution = Institution::factory()->create(['tenant_id' => seoTenant('mif')->id]);
    $institution->types()->attach($type);
    $meeting = Meeting::factory()->create(['description' => '']);
    $meeting->institutions()->attach($institution);
    $url = LocalizedRouteSlugs::route('publicMeetings.show', ['subdomain' => 'mif', 'meeting' => $meeting->id], 'lt');
    $html = $this->get($url)->assertOk()->getContent();

    expect(parseSeoHead($html)['description'])->not->toBeEmpty()
        ->and(HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('meta[name="robots"]')->getAttribute('content'))->toBe('noindex, nofollow');
});

it('preloads the first homepage news image in the server head', function (): void {
    Storage::fake('spatieMediaLibrary');
    $tenant = seoTenant('vusa');
    $content = Content::factory()->create();
    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'news',
        'json_content' => ['title' => 'Naujienos'],
        'options' => ['limit' => 1],
    ]);
    $tenant->homepageContents()->create(['content_id' => $content->id, 'locale' => 'lt']);
    $news = News::factory()->create([
        'tenant_id' => $tenant->id,
        'lang' => 'lt',
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);
    $media = app(AddImageMedia::class)->execute($news, UploadedFile::fake()->image('news.jpg'), 'image');
    $html = $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))->assertOk()->getContent();
    $head = HTMLDocument::createFromString($html, LIBXML_NOERROR)->head;
    $preloads = $head->querySelectorAll('link[rel="preload"][as="image"][fetchpriority="high"]');

    expect(array_map(fn ($link) => $link->getAttribute('href'), iterator_to_array($preloads)))->toContain($media->getUrl());
});
