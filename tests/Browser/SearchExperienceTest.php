<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Typesense\SearchProfiles;
use App\Services\Typesense\TypesenseCollectionConfig;
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
