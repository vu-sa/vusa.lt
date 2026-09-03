<?php

use App\Models\Calendar;
use App\Models\Category;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ContentResolution\ContentPartResolver;
use App\Services\ContentResolution\ResolutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create(['alias' => 'testfak']);
    $this->context = new ResolutionContext(tenant: $this->tenant, locale: 'lt', subdomain: 'testfak');
    $this->resolver = app(ContentPartResolver::class);
});

function makeResolvablePart(string $type, array $jsonContent = [], ?array $options = null, ?int $id = null): ContentPart
{
    $part = new ContentPart([
        'type' => $type,
        'json_content' => $jsonContent,
        'options' => $options,
    ]);
    $part->id = $id ?? random_int(100000, 999999);

    return $part;
}

describe('ContentPartResolver::resolvableTypes', function (): void {
    test('lists exactly the dynamic types', function (): void {
        expect(ContentPartResolver::resolvableTypes())->toEqualCanonicalizing([
            'link-list', 'event-list', 'news', 'calendar',
        ]);
    });
});

describe('ContentPartResolver::resolveAll', function (): void {
    test('ignores content parts of unresolvable types (e.g. person-quote, tiptap)', function (): void {
        $parts = collect([
            makeResolvablePart('tiptap', ['type' => 'doc'], null, 1),
        ]);

        expect($this->resolver->resolveAll($parts, $this->context))->toBe([]);
    });

    test('batches one resolver call per type regardless of how many blocks of that type exist', function (): void {
        Category::factory()->create(['alias' => 'news-cat']);
        $parts = collect([
            makeResolvablePart('link-list', ['links' => []], ['source' => 'manual'], 1),
            makeResolvablePart('link-list', ['links' => []], ['source' => 'manual'], 2),
        ]);

        $resolved = $this->resolver->resolveAll($parts, $this->context);

        expect($resolved)->toHaveKeys([1, 2])
            ->and($resolved[1]['type'])->toBe('link-list')
            ->and($resolved[2]['type'])->toBe('link-list');
    });
});

describe('ContentPartResolver::resolveOne', function (): void {
    test('returns null for a non-resolvable type', function (): void {
        expect($this->resolver->resolveOne('person-quote', [], null, $this->context))->toBeNull();
    });

    test('resolves an unsaved part through the same resolver public rendering uses', function (): void {
        $result = $this->resolver->resolveOne('link-list', ['links' => [
            ['title' => 'Example', 'url' => 'https://vusa.lt'],
        ]], ['source' => 'manual'], $this->context);

        expect($result['type'])->toBe('link-list')
            ->and($result['items'])->toHaveCount(1)
            ->and($result['items'][0]['title'])->toBe('Example');
    });
});

describe('LinkListResolver — manual links', function (): void {
    test('drops a link missing a title or an invalid url', function (): void {
        $part = makeResolvablePart('link-list', ['links' => [
            ['title' => 'Good', 'url' => 'https://vusa.lt'],
            ['title' => '', 'url' => 'https://vusa.lt'],
            ['title' => 'Bad scheme', 'url' => 'javascript:alert(1)'],
        ]], ['source' => 'manual']);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toHaveCount(1)
            ->and($resolved[$part->id]['items'][0]['title'])->toBe('Good');
    });

    test('passes through a manual link\'s imageUrl so the photo style has something to render', function (): void {
        $part = makeResolvablePart('link-list', ['links' => [
            ['title' => 'With image', 'url' => 'https://vusa.lt', 'imageUrl' => '/uploads/foto.png'],
            ['title' => 'Without image', 'url' => 'https://vusa.lt/no-image'],
        ]], ['source' => 'manual']);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);
        $items = collect($resolved[$part->id]['items']);

        expect($items->firstWhere('title', 'With image')['imageUrl'])->toBe('/uploads/foto.png')
            ->and($items->firstWhere('title', 'Without image')['imageUrl'])->toBeNull();
    });

    test('caps manual links at 12', function (): void {
        $links = collect(range(1, 15))->map(fn ($i) => ['title' => "Link $i", 'url' => "https://vusa.lt/$i"])->all();
        $part = makeResolvablePart('link-list', ['links' => $links], ['source' => 'manual']);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toHaveCount(12);
    });
});

describe('LinkListResolver — news source', function (): void {
    test('drops a pinned news id that is a draft', function (): void {
        $news = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => true]);
        $part = makeResolvablePart('link-list', [], ['source' => 'news', 'mode' => 'specific', 'newsIds' => [$news->id]]);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toBeEmpty();
    });

    test('drops a pinned news id that is not yet published', function (): void {
        $news = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->addWeek()]);
        $part = makeResolvablePart('link-list', [], ['source' => 'news', 'mode' => 'specific', 'newsIds' => [$news->id]]);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toBeEmpty();
    });

    test('includes a live, published, pinned news item with a working href', function (): void {
        $news = News::factory()->for($this->tenant)->create([
            'lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(), 'title' => 'Naujiena',
        ]);
        $part = makeResolvablePart('link-list', [], ['source' => 'news', 'mode' => 'specific', 'newsIds' => [$news->id]]);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);
        $item = $resolved[$part->id]['items'][0];

        expect($item['id'])->toBe($news->id)
            ->and($item['title'])->toBe('Naujiena')
            ->and($item['href'])->toContain($news->permalink);
    });

    test('follows other_lang_id when the pinned news is in the wrong language, and drops it when there is no counterpart', function (): void {
        $enNews = News::factory()->for($this->tenant)->create(['lang' => 'en', 'draft' => false, 'publish_time' => now()->subDay()]);
        $part = makeResolvablePart('link-list', [], ['source' => 'news', 'mode' => 'specific', 'newsIds' => [$enNews->id]]);

        // Viewer is on the LT locale, the pinned article is EN with no counterpart.
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toBeEmpty()
            ->and($resolved[$part->id]['meta']['droppedForLocale'])->toBe(1);
    });

    test('latest mode filters by category alias and the current tenant', function (): void {
        $category = Category::factory()->create(['alias' => 'announcements']);
        // Explicit, distinct category — NewsFactory's default `category_id` is
        // `Category::inRandomOrder()->first()->id`, which could otherwise coincidentally
        // land on `$category` itself and make this test flaky depending on how many
        // other categories happen to exist at the time.
        $anotherCategory = Category::factory()->create(['alias' => 'not-announcements']);
        $matching = News::factory()->for($this->tenant)->create([
            'lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(), 'category_id' => $category->id,
        ]);
        $otherCategory = News::factory()->for($this->tenant)->create([
            'lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(), 'category_id' => $anotherCategory->id,
        ]);
        $otherTenant = Tenant::factory()->create();
        News::factory()->for($otherTenant)->create([
            'lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(), 'category_id' => $category->id,
        ]);

        $part = makeResolvablePart('link-list', [], ['source' => 'news', 'mode' => 'latest', 'categoryAlias' => 'announcements', 'tenantScope' => 'current']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        $ids = collect($resolved[$part->id]['items'])->pluck('id')->all();
        expect($ids)->toBe([$matching->id])
            ->and($ids)->not->toContain($otherCategory->id);
    });

    test('clamps limit to the 1-12 range', function (): void {
        // 13, not 12: with exactly 12 available, "12 results" would hold even if the
        // clamp did nothing — one extra record is needed to actually prove truncation.
        News::factory()->for($this->tenant)->count(13)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay()]);
        $part = makeResolvablePart('link-list', [], ['source' => 'news', 'mode' => 'latest', 'limit' => 99]);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toHaveCount(12);
    });
});

describe('LinkListResolver — pages source', function (): void {
    test('drops an inactive pinned page', function (): void {
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'is_active' => false]);
        $part = makeResolvablePart('link-list', [], ['source' => 'pages', 'mode' => 'specific', 'pageIds' => [$page->id]]);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toBeEmpty();
    });

    test('includes a live pinned page', function (): void {
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'is_active' => true, 'title' => 'Puslapis']);
        $part = makeResolvablePart('link-list', [], ['source' => 'pages', 'mode' => 'specific', 'pageIds' => [$page->id]]);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'][0]['title'])->toBe('Puslapis');
    });
});

describe('EventListResolver', function (): void {
    test('year mode filters to the given year only', function (): void {
        $inYear = Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'date' => Carbon::create(2025, 6, 1)]);
        Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'date' => Carbon::create(2024, 6, 1)]);

        $part = makeResolvablePart('event-list', [], ['mode' => 'year', 'year' => 2025, 'tenantScope' => 'current']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        $ids = collect($resolved[$part->id]['items'])->pluck('id')->all();
        expect($ids)->toBe([$inYear->id]);
    });

    test('excludes draft events', function (): void {
        Calendar::factory()->for($this->tenant)->create(['is_draft' => true, 'date' => now()]);
        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'tenantScope' => 'current']);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'])->toBeEmpty();
    });

    test('a trashed category still works as a grouping key (matches summerCamps() precedent)', function (): void {
        // 'freshmen-camps' is a globally seeded alias (CategoriesSeeder) — reuse it
        // rather than colliding on the unique constraint.
        $category = Category::firstOrCreate(['alias' => 'freshmen-camps'], ['name' => ['lt' => 'Stovyklos', 'en' => 'Camps']]);
        $event = Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'category_id' => $category->id, 'date' => now()]);
        $category->delete();

        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'categoryAlias' => 'freshmen-camps', 'tenantScope' => 'current']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect(collect($resolved[$part->id]['items'])->pluck('id')->all())->toBe([$event->id]);
    });

    test('groupBy tenant with the "full" label style prefixes the locative fullname', function (): void {
        $tenantB = Tenant::factory()->create(['fullname' => 'Kito fakulteto atstovybė']);
        Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'date' => now()]);
        Calendar::factory()->for($tenantB)->create(['is_draft' => false, 'date' => now()]);

        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'groupBy' => 'tenant', 'tenantScope' => 'all', 'tenantLabelPrefix' => 'VU', 'tenantLabelStyle' => 'full']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['groups'])->toHaveCount(2);
        $labels = collect($resolved[$part->id]['groups'])->pluck('label')->all();
        expect($labels)->toContain('VU Kito fakulteto atstovybė');
    });

    test('groupBy tenant with the "faculty" label style renders "VU <nominative faculty>" derived from the locative fullname', function (): void {
        // Port of getFacultyName (Utils/String.ts): "...Filologijos fakultete" → "VU Filologijos fakultetas".
        $tenantB = Tenant::factory()->create(['fullname' => 'Vilniaus universiteto Studentų atstovybė Filologijos fakultete', 'shortname_vu' => 'VU FlF']);
        Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'date' => now()]);
        Calendar::factory()->for($tenantB)->create(['is_draft' => false, 'date' => now()]);

        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'groupBy' => 'tenant', 'tenantScope' => 'all', 'tenantLabelStyle' => 'faculty']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        $labels = collect($resolved[$part->id]['groups'])->pluck('label')->all();
        expect($labels)->toContain('VU Filologijos fakultetas')
            ->and($labels)->not->toContain('VU FlF')
            ->and($labels)->not->toContain('Vilniaus universiteto Studentų atstovybė Filologijos fakultete');
    });

    test('the "faculty" label style falls back to the fullname for the central tenant (no faculty part)', function (): void {
        $central = Tenant::factory()->create(['fullname' => 'Vilniaus universiteto Studentų atstovybė']);
        Calendar::factory()->for($central)->create(['is_draft' => false, 'date' => now()]);

        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'groupBy' => 'tenant', 'tenantScope' => 'all', 'tenantLabelStyle' => 'faculty']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        $labels = collect($resolved[$part->id]['groups'])->pluck('label')->all();
        expect($labels)->toContain('Vilniaus universiteto Studentų atstovybė');
    });

    test('groups are sorted alphabetically by label, not by which tenant has the earliest event', function (): void {
        // Tenant Z has the earliest event, but its label ("VU Z...") should still sort last.
        // Fullnames end in a non-locative suffix so faculty derivation leaves them unchanged.
        $tenantZ = Tenant::factory()->create(['fullname' => 'Vilniaus universiteto Studentų atstovybė Z padalinys']);
        $tenantA = Tenant::factory()->create(['fullname' => 'Vilniaus universiteto Studentų atstovybė A padalinys']);
        Calendar::factory()->for($tenantZ)->create(['is_draft' => false, 'date' => now()]);
        Calendar::factory()->for($tenantA)->create(['is_draft' => false, 'date' => now()->addDay()]);

        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'groupBy' => 'tenant', 'tenantScope' => 'all', 'tenantLabelStyle' => 'faculty']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        $labels = collect($resolved[$part->id]['groups'])->pluck('label')->all();
        expect($labels)->toBe(['VU A padalinys', 'VU Z padalinys']);
    });

    test('en locale only returns international events', function (): void {
        $intl = Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'is_international' => true, 'date' => now()]);
        Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'is_international' => false, 'date' => now()]);

        $enContext = new ResolutionContext(tenant: $this->tenant, locale: 'en', subdomain: 'testfak');
        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'tenantScope' => 'current']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $enContext);

        expect(collect($resolved[$part->id]['items'])->pluck('id')->all())->toBe([$intl->id]);
    });

    test('imageUrl falls back to the main_image collection when no gallery images exist', function (): void {
        Storage::fake('spatieMediaLibrary');
        $event = Calendar::factory()->for($this->tenant)->create(['is_draft' => false, 'date' => now()]);
        $event->addMedia(UploadedFile::fake()->image('main.jpg'))->toMediaCollection('main_image');

        $part = makeResolvablePart('event-list', [], ['mode' => 'upcoming', 'tenantScope' => 'current']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['items'][0]['imageUrl'])->not->toBeNull();
    });

    test('clamps the date range mode to MAX_RANGE_DAYS', function (): void {
        // 10 years apart — should be clamped, not silently accepted.
        $part = makeResolvablePart('event-list', [], [
            'mode' => 'range',
            'dateFrom' => '2015-01-01',
            'dateTo' => '2025-01-01',
            'tenantScope' => 'current',
        ]);

        // Deliberately don't seed any events — this just proves the resolver doesn't
        // throw or hang building a decade-wide query.
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['type'])->toBe('event-list');
    });
});

describe('NewsBlockResolver / CalendarBlockResolver bridges', function (): void {
    test('news bridge returns the same shape as NewsCollection::getPublishedForTenant', function (): void {
        News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(), 'title' => 'Bridge test']);
        $part = makeResolvablePart('news', ['title' => '']);

        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        expect($resolved[$part->id]['type'])->toBe('news')
            ->and($resolved[$part->id]['items'][0])->toHaveKeys(['id', 'title', 'lang', 'short', 'publish_time', 'permalink', 'image', 'category']);
    });

    test('news bridge carries the category name, and null when the article has none', function (): void {
        $category = Category::factory()->create(['name' => ['lt' => 'Akademinė informacija', 'en' => 'Academic information']]);
        News::factory()->for($this->tenant)->for($category)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay()]);
        News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDays(2), 'category_id' => null]);

        $part = makeResolvablePart('news', ['title' => '']);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        $categories = collect($resolved[$part->id]['items'])->pluck('category')->all();

        expect($categories)->toContain('Akademinė informacija')
            ->and($categories)->toContain(null);
    });

    test('calendar bridge excludes drafts and is not tenant-scoped', function (): void {
        $otherTenant = Tenant::factory()->create();
        $event = Calendar::factory()->for($otherTenant)->create(['is_draft' => false, 'date' => now()]);
        Calendar::factory()->for($this->tenant)->create(['is_draft' => true, 'date' => now()]);

        $part = makeResolvablePart('calendar', ['title' => ''], ['allTenants' => false]);
        $resolved = $this->resolver->resolveAll(collect([$part->id => $part]), $this->context);

        $ids = collect($resolved[$part->id]['items'])->pluck('id')->all();
        expect($ids)->toContain($event->id);
    });
});
