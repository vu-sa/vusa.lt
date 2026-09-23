<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function runSummerCampYearPagesMigration(): void
{
    (require base_path('database/migrations/2026_09_23_185449_seed_summer_camp_year_pages.php'))->up();
}

function summerCampSourcePage(Tenant $tenant, string $lang, string $permalink): Page
{
    $content = Content::factory()->create();

    $parts = [
        ['hero', ['title' => 'Pirmakursių stovyklos'], ['variant' => 'panel']],
        ['event-list', [], ['mode' => 'year', 'year' => 2026, 'eventTypeSlug' => 'stovykla', 'groupBy' => 'tenant']],
        ['content-grid', [], []],
        ['link-list', ['links' => []], ['source' => 'manual', 'title' => 'Kitų metų stovyklos']],
    ];

    foreach ($parts as $order => [$type, $json, $options]) {
        ContentPart::factory()->create([
            'content_id' => $content->id,
            'type' => $type,
            'json_content' => $json,
            'options' => $options,
            'order' => $order,
        ]);
    }

    return Page::factory()->create([
        'tenant_id' => $tenant->id,
        'content_id' => $content->id,
        'lang' => $lang,
        'permalink' => $permalink,
        'title' => 'Pirmakursių stovyklos',
        'is_active' => true,
    ]);
}

beforeEach(function (): void {
    $this->tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        ['shortname' => 'VU SA', 'fullname' => 'Vilniaus universiteto Studentų atstovybė', 'type' => 'pagrindinis'],
    );

    $this->lt = summerCampSourcePage($this->tenant, 'lt', 'pirmakursiu-stovyklos');
    $this->en = summerCampSourcePage($this->tenant, 'en', 'freshmen-camps');
});

test('creates a child page per past year with the event list pinned to that year', function (): void {
    runSummerCampYearPagesMigration();

    $page = Page::query()->where('permalink', 'pirmakursiu-stovyklos-2024')->firstOrFail();
    $parts = ContentPart::query()->where('content_id', $page->content_id)->orderBy('order')->get();

    expect($page->parent_id)->toBe($this->lt->id)
        ->and($page->title)->toBe('2024 m. pirmakursių stovyklos')
        ->and($page->otherLanguagePage?->permalink)->toBe('freshmen-camps-2024')
        ->and($parts->pluck('type')->all())->toBe(['hero', 'event-list', 'link-list'])
        ->and($parts[1]->options['year'])->toBe(2024);

    $archiveLinks = collect(ContentPart::query()
        ->where('content_id', $this->lt->content_id)
        ->where('type', 'link-list')
        ->firstOrFail()->json_content['links']);

    expect($archiveLinks->pluck('url'))->toContain($page->publicUrl())
        ->and($archiveLinks)->toHaveCount(4);
});

test('redirects the retired year URL to its content page', function (): void {
    runSummerCampYearPagesMigration();

    $page = Page::query()->where('permalink', 'freshmen-camps-2023')->firstOrFail();

    $this->get(route('page', ['subdomain' => 'www', 'lang' => 'en', 'permalink' => 'freshmen-camps/2023']))
        ->assertStatus(301)
        ->assertRedirect($page->publicUrl());
});

test('running it twice creates no duplicate pages', function (): void {
    runSummerCampYearPagesMigration();
    runSummerCampYearPagesMigration();

    expect(Page::query()->where('permalink', 'like', 'pirmakursiu-stovyklos-%')->count())->toBe(4);
});
