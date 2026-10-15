<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Typesense\DocumentRecommendations;
use App\Services\Typesense\SearchProfiles;
use App\Services\Typesense\TypesenseCollectionConfig;
use App\Settings\DocumentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

pest()->use(RefreshDatabase::class);

function seedSearchExperience(): void
{
    usesTypesenseInBrowser();
    config(['scout.queue' => false]);
    $client = app(Client::class);
    foreach (TypesenseCollectionConfig::getAllModelClasses() as $model) {
        $name = (new $model)->searchableAs();
        try {
            $client->collections[$name]->delete();
        } catch (ObjectNotFound) {
        }
        $client->collections->create(['name' => $name, ...config('scout.typesense.model-settings.'.$model.'.collection-schema')]);
        Cache::forever(SearchProfiles::cacheKey(substr($name, strlen(config('scout.prefix')))), SearchProfiles::VERSION);
    }
    $news = News::factory()->for(Tenant::query()->where('alias', 'vusa')->firstOrFail())->create([
        'title' => 'Paieškos vadovas', 'lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(),
    ]);
    $news->content->parts()->first()->update(['json_content' => ['type' => 'doc', 'content' => [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'unikalusturinys studentams']]],
    ]]]);
    for ($index = 1; $index <= 9; $index++) {
        $news->tags()->attach(Tag::factory()->create(['name' => ['lt' => 'Tema '.$index, 'en' => 'Topic '.$index]]));
    }
}

it('renders document prefix search across widths and themes without JavaScript errors', function (): void {
    seedSearchExperience();
    $document = Document::factory()->create([
        'title' => 'VU SA Įstatai (nuo 2025 m.)', 'language' => 'Lietuvių',
        'content_type' => 'Veiklą reglamentuojantys dokumentai', 'status' => DocumentStatus::Published,
        'effective_date' => null, 'expiration_date' => null,
    ]);
    $settings = app(DocumentSettings::class);
    $settings->recommendations = [['document_id' => (string) $document->id, 'phrases' => ['įstatai'], 'enabled' => true, 'show_without_query' => true]];
    $settings->save();
    app(DocumentRecommendations::class)->synchronize();
    $page = visitPublicSubdomain('www', '/lt/dokumentai?q=įstatų');
    $page->assertVisible('[data-slot="recommended-documents"]');
    $page->assertVisible('h3 mark:visible');
    expect($page->script('document.querySelectorAll("[data-slot=recommended-documents] li").length'))->toBe(1);
    $page->click('[data-slot="cookie-consent"] button:has-text("Supratau")');
    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 844);
            expect($page->script('document.documentElement.scrollWidth > innerWidth + 1'))->toBeFalse();
            if (getenv('SEARCH_SCREENSHOTS') && $dark && in_array($width, [390, 1440], true)) {
                $page->screenshot(fullPage: false, filename: 'document-highlight-'.$width);
            }
        }
    }
    $page->assertNoJavaScriptErrors();
    $page->navigate('/lt/dokumentai');
    $page->assertVisible('[data-slot="recommended-documents"]');
    $page->click('button[aria-label="Rikiuoti"]');
    $page->assertVisible('button[role="radio"][aria-checked="true"]:has-text("Naujausi pirmi")');
    $page->click('button[role="radio"]:has-text("Naujausi pirmi")');
    $page->assertVisible('[data-slot="recommended-documents"]');
});

it('saves a selected document recommendation through the settings form', function (): void {
    seedSearchExperience();
    Document::factory()->create(['title' => 'VU SA Įstatai', 'language' => 'Lietuvių', 'status' => DocumentStatus::Published]);
    $page = loginAsAdmin(makeAdminUser());
    $page->navigate('/mano/settings/documents');
    $page->click('[data-testid="add-document-recommendation"]');
    $page->fill('[data-slot="dialog-content"] input[type="text"]', 'įstatai');
    $page->click('[data-slot="search-hit-row"]');
    $page->click('[data-slot="dialog-content"] button:has-text("Pridėti pasirinktus")');
    $page->fill('#recommendation-phrases-0', 'įstatai, VU SA įstatai');
    $page->click('[data-testid="form-page-save"]');
    $page->assertDontSee('Pasirinkta recommendations');
    $page->navigate('/mano/settings/documents');
    $page->assertValue('#recommendation-phrases-0', 'įstatai, VU SA įstatai');
    foreach ([390, 820, 1440] as $width) {
        $page->resize($width, 844);
        expect($page->script('document.documentElement.scrollWidth > innerWidth + 1'))->toBeFalse();
    }
    $page->assertNoJavaScriptErrors();
});

it('renders searchable public filters and body excerpts across widths and themes', function (): void {
    seedSearchExperience();
    $page = visitPublicSubdomain('www', '/lt/naujienos?q=unikalusturinys');
    $page->assertVisible('[data-slot="search-match"]');
    $page->click('[data-slot="cookie-consent"] button:has-text("Supratau")');
    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 844);
            expect($page->script('document.documentElement.scrollWidth > innerWidth + 1'))->toBeFalse();
        }
    }
    $page->click('button:has-text("Filtrai")');
    $page->click('[data-slot="public-filter-popover-trigger"] >> nth=2');
    $page->assertVisible('[data-slot="public-filter-popover-content"] input[type="search"]');
    $page->fill('[data-slot="public-filter-popover-content"] input[type="search"]', 'Tema 9');
    $page->assertDontSee('Ieškoma parinkčių...');
    $page->assertSee('Tema 9')->assertNoJavaScriptErrors();
});

it('renders admin body excerpts and filters without overflow across widths and themes', function (): void {
    seedSearchExperience();
    $page = loginAsAdmin(makeAdminUser());
    $page->navigate('/mano/news?q=unikalusturinys');
    $page->assertVisible('[data-slot="search-match"]');
    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 844);
            expect($page->script('document.documentElement.scrollWidth > innerWidth + 1'))->toBeFalse();
        }
    }
    $page->resize(390, 844);
    $page->click('[data-slot="collection-filters-toggle"]');
    $page->assertNoJavaScriptErrors();
});

it('keeps the most relevant record first in the command palette across workspaces', function (string $query, string $path, string $expected): void {
    seedSearchExperience();
    $user = User::factory()->create(['name' => 'Austėja Petrauskaitė']);
    $duty = Duty::factory()->for(Institution::factory())->create(['name' => ['lt' => 'Prezidentas', 'en' => 'President']]);
    $duty->users()->attach($user, ['start_date' => now()->subDay()]);
    $duty->refresh()->searchable();
    $user->refresh()->searchable();
    $page = loginAsAdmin(makeAdminUser());
    $page->navigate($path);
    waitForInertiaRender($page);
    $page->click('[data-tour="command-palette"]:visible');
    $page->fill('[data-slot="dialog-content"] input[type="text"]', $query);
    $page->assertVisible('[data-slot="search-hit-row"][data-collection="'.$expected.'"]');
    expect($page->script('document.querySelector("[data-slot=search-hit-row]").dataset.collection'))->toBe($expected);
    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 844);
            expect($page->script('document.documentElement.scrollWidth > innerWidth + 1'))->toBeFalse();
            if (getenv('SEARCH_SCREENSHOTS') && $width === 390 && $dark) {
                $page->screenshot(fullPage: false, filename: 'search-ranking-'.$expected);
            }
        }
    }
    $page->assertNoJavaScriptErrors();
})->with([
    'person from ViSAK' => ['Austėja Petrauskaitė', '/mano/meetings', 'users'],
    'duty from Organisation' => ['Prezidentė', '/mano/users', 'duties'],
]);
