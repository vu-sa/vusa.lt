<?php

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    usesTypesense();

    config([
        'scout.prefix' => 'testing_other_lang_picker_',
        'scout.queue' => false,
    ]);
});

/**
 * Regression test for the bug report: newly created (and drafted) news did not
 * show up in the "Kitos kalbos naujiena" picker (NewsForm.vue) because it
 * searched the same publication-gated Typesense collection the public site
 * uses. The picker builds `tenant_ids:[...] && lang:=<other>` as its base
 * filter (NewsForm.vue's otherLangBaseFilterBy) — this reproduces that query
 * against the admin `News` index directly.
 */
test('a draft news article in the opposite language is findable through the picker filter', function (): void {
    $tenant = Tenant::query()->first();
    $title = 'Picker Draft News '.Str::uuid()->toString();

    $draft = News::factory()->create([
        'tenant_id' => $tenant->id,
        'title' => $title,
        'lang' => 'en',
        'draft' => true,
    ]);

    $hits = News::search($title)
        ->where('lang', 'en')
        ->whereIn('tenant_ids', [$tenant->id])
        ->raw()['hits'] ?? [];

    $ids = collect($hits)->pluck('document.id')->map(fn ($id) => (string) $id)->all();

    expect($ids)->toContain((string) $draft->id);
});
