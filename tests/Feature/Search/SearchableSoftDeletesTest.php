<?php

use App\Models\Calendar;
use App\Models\Category;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicInstitution;
use App\Models\PublicNews;
use App\Models\PublicPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'scout.prefix' => 'testing_soft_deletes_',
        'scout.queue' => false,
        'scout.soft_delete' => false,
    ]);
});

function typesenseSearchHitIds(string $searchableModel, string $query): array
{
    return collect($searchableModel::search($query)->raw()['hits'] ?? [])
        ->pluck('document.id')
        ->map(fn ($id) => (string) $id)
        ->all();
}

function expectTypesenseSearchContains(string $searchableModel, string $query, Model $model): void
{
    expect(typesenseSearchHitIds($searchableModel, $query))
        ->toContain((string) $model->getKey());
}

function expectTypesenseSearchMissing(string $searchableModel, string $query, Model $model): void
{
    expect(typesenseSearchHitIds($searchableModel, $query))
        ->not->toContain((string) $model->getKey());
}

function assertTypesenseSoftDeleteLifecycle(Model $model, string $query, ?string $searchableModel = null): void
{
    $searchableModel ??= $model::class;

    $model->searchable();
    expectTypesenseSearchContains($searchableModel, $query, $model);

    $model->delete();
    expectTypesenseSearchMissing($searchableModel, $query, $model);

    $model->restore();
    expectTypesenseSearchContains($searchableModel, $query, $model);

    $model->forceDelete();
    expectTypesenseSearchMissing($searchableModel, $query, $model);
}

test('news leaves the admin search index when soft deleted and returns when restored', function (): void {
    $query = 'Soft Delete News '.Str::uuid()->toString();
    $category = Category::factory()->create();

    $news = News::factory()->create([
        'category_id' => $category->id,
        'title' => $query,
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);

    expect($news->shouldBeSearchable())->toBeTrue();

    assertTypesenseSoftDeleteLifecycle($news, $query);
});

test('page leaves the admin search index when soft deleted and returns when restored', function (): void {
    $query = 'Soft Delete Page '.Str::uuid()->toString();
    $category = Category::factory()->create();

    $page = Page::factory()->active()->create([
        'category_id' => $category->id,
        'title' => $query,
        'lang' => 'lt',
    ]);

    expect($page->shouldBeSearchable())->toBeTrue();

    assertTypesenseSoftDeleteLifecycle($page, $query);
});

test('public news index follows parent news soft delete lifecycle', function (): void {
    $query = 'Soft Delete Public News '.Str::uuid()->toString();
    $category = Category::factory()->create();

    $news = News::factory()->create([
        'category_id' => $category->id,
        'title' => $query,
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);

    // News::saved() already synced PublicNews — no manual ->searchable() needed.
    expectTypesenseSearchContains(PublicNews::class, $query, $news);

    $news->delete();
    expectTypesenseSearchMissing(PublicNews::class, $query, $news);

    $news->restore();
    expectTypesenseSearchContains(PublicNews::class, $query, $news);

    $news->forceDelete();
    expectTypesenseSearchMissing(PublicNews::class, $query, $news);
});

test('public pages index follows parent page soft delete lifecycle', function (): void {
    $query = 'Soft Delete Public Page '.Str::uuid()->toString();
    $category = Category::factory()->create();

    $page = Page::factory()->active()->create([
        'category_id' => $category->id,
        'title' => $query,
        'lang' => 'lt',
    ]);

    expectTypesenseSearchContains(PublicPage::class, $query, $page);

    $page->delete();
    expectTypesenseSearchMissing(PublicPage::class, $query, $page);

    $page->restore();
    expectTypesenseSearchContains(PublicPage::class, $query, $page);

    $page->forceDelete();
    expectTypesenseSearchMissing(PublicPage::class, $query, $page);
});

test('calendar leaves public search when soft deleted and returns when restored', function (): void {
    $query = 'Soft Delete Calendar '.Str::uuid()->toString();

    $calendar = Calendar::factory()->create([
        'title' => ['lt' => $query, 'en' => $query],
        'is_draft' => false,
    ]);

    expect($calendar->shouldBeSearchable())->toBeTrue();

    assertTypesenseSoftDeleteLifecycle($calendar, $query);
});

test('public institution index follows parent institution soft delete lifecycle', function (): void {
    $query = 'Soft Delete Institution '.Str::uuid()->toString();

    $institution = Institution::factory()->create([
        'name' => ['lt' => $query, 'en' => $query],
        'short_name' => ['lt' => $query, 'en' => $query],
        'is_active' => 1,
    ]);

    $publicInstitution = PublicInstitution::query()->findOrFail($institution->id);
    expect($publicInstitution->shouldBeSearchable())->toBeTrue();

    $publicInstitution->searchable();
    expectTypesenseSearchContains(PublicInstitution::class, $query, $institution);

    $institution->delete();
    expectTypesenseSearchMissing(PublicInstitution::class, $query, $institution);

    $institution->restore();
    expectTypesenseSearchContains(PublicInstitution::class, $query, $institution);

    $institution->forceDelete();
    expectTypesenseSearchMissing(PublicInstitution::class, $query, $institution);
});
