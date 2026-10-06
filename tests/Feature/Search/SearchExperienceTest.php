<?php

use App\Models\Document;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicNews;
use App\Models\PublicPage;
use App\Models\Resource;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Typesense\DocumentRecommendations;
use App\Services\Typesense\SearchProfiles;
use App\Services\Typesense\SearchText;
use App\Services\Typesense\TypesenseCollectionConfig;
use App\Settings\DocumentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Typesense\Client;

pest()->use(RefreshDatabase::class);

test('document search finds statutes first when only the word prefix is entered', function (): void {
    usesTypesense();
    $institution = Institution::factory()->create();
    $attributes = ['institution_id' => $institution->id, 'language' => 'Lietuvių', 'is_active' => true, 'summary' => '', 'effective_date' => null, 'expiration_date' => null];
    $statutes = Document::factory()->create([...$attributes, 'title' => 'VU SA Įstatai (nuo 2025 m.)', 'content_type' => 'Veiklą reglamentuojantys dokumentai', 'document_date' => '2025-05-17']);
    $resolution = Document::factory()->create([...$attributes, 'title' => 'VU SA Parlamento nutarimas dėl įstatų keitimo', 'content_type' => 'VU SA Parlamento nutarimai', 'document_date' => '2026-05-17']);
    $result = app(Client::class)->collections[$statutes->searchableAs()]->documents->search([
        ...SearchProfiles::all()['documents']['parameters'],
        'q' => 'įstat', 'sort_by' => '_text_match:desc,document_date:desc',
        'filter_by' => 'is_active:=true && id:=['.$statutes->id.','.$resolution->id.']',
    ]);
    expect($result['found'])->toBe(2)
        ->and($result['hits'][0]['document']['id'])->toBe((string) $statutes->id)
        ->and(collect($result['hits'][0]['highlights'])->firstWhere('field', 'title')['snippet'])->toContain('⟦');
});

test('admin ranking prefers the record identity over names and positions mentioned in related records', function (): void {
    usesTypesense();
    config(['scout.queue' => false]);
    $user = User::factory()->create(['name' => 'Austėja Petrauskaitė']);
    $duty = Duty::factory()->for(Institution::factory())->create(['name' => ['lt' => 'Prezidentas', 'en' => 'President']]);
    $duty->users()->attach($user, ['start_date' => now()->subDay()]);
    $duty->refresh()->searchable();
    $user->refresh()->searchable();
    foreach (['duties', 'users'] as $base) {
        Cache::forever(SearchProfiles::cacheKey($base), SearchProfiles::VERSION);
    }
    $client = app(Client::class);
    foreach (['Austėja Petrauskaitė' => 'users', 'Austėja' => 'users', 'Petrauskaitė' => 'users', 'Prezidentė' => 'duties', 'Prezidentas' => 'duties', 'President' => 'duties'] as $query => $expected) {
        $searches = [];
        foreach (['users' => User::class, 'duties' => Duty::class] as $base => $model) {
            $profile = SearchProfiles::all(admin: true)[$base];
            $searches[] = ['collection' => (new $model)->searchableAs(), ...$profile['parameters'], 'q' => $query, 'sort_by' => '_text_match:desc,'.$profile['defaultSort']];
        }
        $response = $client->multiSearch->perform(['searches' => $searches]);
        $hits = collect($response['results'])->flatMap(fn ($result, $index) => collect($result['hits'])->map(fn ($hit) => ['collection' => ['users', 'duties'][$index], 'score' => (int) $hit['text_match_info']['score'], 'info' => $hit['text_match_info']]));
        expect($hits->sortByDesc('score')->first()['collection'])->toBe($expected);
    }
});

test('admin profiles use consistent identity weights and retain low weights for related records and prose', function (): void {
    $profiles = SearchProfiles::all(admin: true);
    foreach ($profiles as $profile) {
        $params = $profile['parameters'];
        $weights = array_combine(explode(',', $params['query_by']), array_map(intval(...), explode(',', $params['query_by_weights'])));
        expect($params['text_match_type'])->toBe('max_weight')
            ->and($params['drop_tokens_threshold'])->toBe(0);
        foreach ($weights as $field => $weight) {
            if (in_array($field, ['name', 'name_lt', 'name_en', 'title', 'title_lt', 'title_en'], true)) {
                expect($weight)->toBe(127);
            }
            if (in_array($field, ['current_user_names', 'previous_user_names', 'current_duty_names', 'previous_duty_names', 'institution_name_lt', 'institution_name_en', 'user_names', 'meeting_title'], true)) {
                expect($weight)->toBeLessThanOrEqual(6);
            }
        }
    }
    expect(SearchProfiles::all()['users']['parameters'])->not->toHaveKey('text_match_type');
});

test('profiles activate new fields only after the collection has been rebuilt', function (): void {
    config(['scout.prefix' => 'profile_gate_']);
    Cache::forget(SearchProfiles::cacheKey('public_pages'));
    $legacy = SearchProfiles::all()['public_pages'];
    expect($legacy['version'])->toBe(1)
        ->and(explode(',', $legacy['parameters']['query_by']))->not->toContain('body');

    Cache::forever(SearchProfiles::cacheKey('public_pages'), SearchProfiles::VERSION);
    $current = SearchProfiles::all()['public_pages'];
    expect($current['version'])->toBe(2)
        ->and($current['parameters']['query_by'])->toContain('body,search_text_lt,search_text_en')
        ->and(explode(',', $current['parameters']['query_by']))->toHaveSameSize(explode(',', $current['parameters']['query_by_weights']))
        ->and($current['parameters']['exclude_fields'])->toContain('body');
    Cache::forever(SearchProfiles::cacheKey('public_pages'), (string) SearchProfiles::VERSION);
    expect(SearchProfiles::all()['public_pages']['version'])->toBe(2);
    config(['scout.typesense.search-profile-version' => 1]);
    expect(SearchProfiles::all()['public_pages']['version'])->toBe(1);
    Cache::forget(SearchProfiles::cacheKey('public_pages'));
});

test('search text uses actual translations and excludes personal and institutional names', function (): void {
    $resource = Resource::factory()->make(['name' => ['lt' => 'Palapinė'], 'description' => ['lt' => '<p>Studentams &amp; atstovams</p>', 'en' => '<p>For students</p>']]);
    $text = SearchText::forModel($resource);
    expect($text['search_text_lt'])->toBe('Palapinė Studentams & atstovams')
        ->and($text['search_text_en'])->toBe('For students');
    $user = new User(['name' => 'Jonas Jonaitis', 'email' => 'jonas@example.com']);
    expect(SearchText::forModel($user))->toBe(['search_text_lt' => '', 'search_text_en' => '']);
});

test('committed block edits and removals update admin and public bodies', function (string $model, string $mirror): void {
    usesTypesense();
    config(['scout.queue' => false]);
    $record = $model::factory()->create($model === News::class
        ? ['draft' => false, 'publish_time' => now()->subHour(), 'lang' => 'lt']
        : ['is_active' => true, 'lang' => 'lt']);
    $part = $record->content->parts()->first();
    $part->update(['json_content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'unikalusturinys']]]]]]);
    $client = app(Client::class);
    foreach ([$model, $mirror] as $class) {
        $collection = $client->collections[(new $class)->searchableAs()];
        expect($collection->documents[(string) $record->id]->retrieve()['body'])->toBe('unikalusturinys');
    }
    DB::transaction(function () use ($part): void {
        $part->update(['json_content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'pakeistasturinys']]]]]]);
    });
    foreach ([$model, $mirror] as $class) {
        expect($client->collections[(new $class)->searchableAs()]->documents[(string) $record->id]->retrieve()['body'])->toBe('pakeistasturinys');
    }
    expect(fn () => DB::transaction(function () use ($part): void {
        $part->update(['json_content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'rollbackturinys']]]]]]);
        throw new RuntimeException('Roll back the body edit');
    }))->toThrow(RuntimeException::class);
    foreach ([$model, $mirror] as $class) {
        expect($client->collections[(new $class)->searchableAs()]->documents[(string) $record->id]->retrieve()['body'])->toBe('pakeistasturinys');
    }
    $part->delete();
    foreach ([$model, $mirror] as $class) {
        expect($client->collections[(new $class)->searchableAs()]->documents[(string) $record->id]->retrieve()['body'])->toBe('');
    }
})->with([[News::class, PublicNews::class], [Page::class, PublicPage::class]]);

test('real Typesense ranks title matches above body matches and returns safe body highlights without the body payload', function (): void {
    usesTypesense();
    config(['scout.queue' => false]);
    $tenant = Tenant::query()->first();
    $title = Page::factory()->create(['tenant_id' => $tenant->id, 'title' => 'Bendrabučių tvarka', 'is_active' => true, 'lang' => 'lt']);
    $body = Page::factory()->create(['tenant_id' => $tenant->id, 'title' => 'Apgyvendinimas', 'is_active' => true, 'lang' => 'lt']);
    $body->content->parts()->first()->update(['json_content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Bendrabučių tvarka studentams']]]]]]);
    Cache::forever(SearchProfiles::cacheKey('public_pages'), SearchProfiles::VERSION);
    $profile = SearchProfiles::all()['public_pages'];
    $result = app(Client::class)->collections[(new PublicPage)->searchableAs()]->documents->search([
        ...$profile['parameters'], 'q' => 'Bendrabučių', 'sort_by' => '_text_match:desc,created_at:desc',
        'filter_by' => 'id:=['.$title->id.','.$body->id.']',
    ]);
    expect(array_column(array_column($result['hits'], 'document'), 'id'))->toBe([(string) $title->id, (string) $body->id])
        ->and($result['hits'][1]['document'])->not->toHaveKey('body')
        ->and(collect($result['hits'][1]['highlights'])->firstWhere('field', 'body')['snippet'])->toContain('⟦Bendrabučių⟧');
});

test('native Lithuanian and English stemming finds inflected words', function (): void {
    usesTypesense();
    config(['scout.queue' => false]);
    $lt = Page::factory()->create(['title' => 'Studentams', 'lang' => 'lt', 'is_active' => true]);
    $en = Page::factory()->create(['title' => 'Representatives', 'lang' => 'en', 'is_active' => true]);
    $collection = app(Client::class)->collections[(new PublicPage)->searchableAs()];
    foreach ([[$lt, 'search_text_lt', 'studentas'], [$en, 'search_text_en', 'representative']] as [$page, $field, $query]) {
        $result = $collection->documents->search(['q' => $query, 'query_by' => $field, 'num_typos' => 0, 'prefix' => false, 'filter_by' => 'id:='.$page->id]);
        expect($result['found'])->toBe(1);
    }
});

test('document recommendations match word forms, prefixes and any words of a phrase while respecting settings and publication', function (): void {
    usesTypesense();
    $statutes = Document::factory()->create(['title' => 'VU SA Įstatai', 'language' => 'Lietuvių', 'is_active' => true]);
    $other = Document::factory()->create(['title' => 'VU SA nuostatai', 'language' => 'Lietuvių', 'is_active' => true]);
    $settings = app(DocumentSettings::class);
    $service = app(DocumentRecommendations::class);
    $rule = ['document_id' => (string) $statutes->id, 'phrases' => ['VU SA įstatai'], 'enabled' => true, 'show_without_query' => false];
    // Earlier tests in this process leave phrases under the same prefix, and document ids repeat.
    $service->synchronize();
    expect($service->matchingIds('įstatai'))->toBeEmpty();
    $settings->recommendations = [$rule];
    $settings->save();
    expect($service->matchingIds('įstatai'))->toBeEmpty();
    $service->synchronize();
    foreach (['įstat', 'įstatai', 'įstatų', 'įstatus', 'SA įstat', 'VU SA įstatų'] as $query) {
        expect($service->matchingIds($query))->toBe([(string) $statutes->id]);
    }
    foreach (['', '*', 'įstatymas', 'pakeisti įstatai'] as $query) {
        expect($service->matchingIds($query))->toBeEmpty();
    }
    $this->getJson(route('api.v1.documents.recommendations', ['q' => 'įstatų']))->assertOk()->assertJsonPath('data.ids', [(string) $statutes->id]);
    $this->getJson(route('api.v1.documents.recommendations', ['q' => str_repeat('a', 201)]))->assertUnprocessable();

    $settings->recommendations = [
        [...$rule, 'document_id' => (string) $other->id, 'phrases' => ['įstatai'], 'show_without_query' => true],
        $rule,
    ];
    $settings->save();
    $service->synchronize();
    expect($service->matchingIds('įstatų'))->toBe([(string) $other->id, (string) $statutes->id])
        ->and($service->matchingIds(''))->toBe([(string) $other->id]);
    $statutes->update(['is_active' => false]);
    expect($service->matchingIds('įstatai'))->toBe([(string) $other->id]);
    $other->delete();
    expect($service->matchingIds('įstatai'))->toBeEmpty();
    $settings->recommendations = [[...$rule, 'enabled' => false]];
    $settings->save();
    $service->synchronize();
    expect($service->matchingIds('įstatai'))->toBeEmpty();
});

test('important document types break relevance ties and pinned documents obey search filters', function (): void {
    usesTypesense();
    $attributes = ['title' => 'VU SA dokumentas', 'summary' => '', 'language' => 'Lietuvių', 'is_active' => true];
    $important = Document::factory()->create([...$attributes, 'content_type' => 'Įstatai', 'document_date' => '2024-01-01']);
    $ordinary = Document::factory()->create([...$attributes, 'content_type' => 'Nutarimai', 'document_date' => '2026-01-01']);
    $collection = app(Client::class)->collections[$important->searchableAs()];
    $params = ['q' => 'dokumentas', 'query_by' => 'title', 'filter_by' => 'id:=['.$important->id.','.$ordinary->id.']', 'sort_by' => '_text_match:desc,_eval(content_type:=[`Įstatai`,`Šablonai`]):desc,document_date:desc'];
    expect($collection->documents->search($params)['hits'][0]['document']['id'])->toBe((string) $important->id)
        ->and($collection->documents->search([...$params, 'sort_by' => 'document_date:desc'])['hits'][0]['document']['id'])->toBe((string) $ordinary->id);
    $pins = ['pinned_hits' => $important->id.':1', 'filter_curated_hits' => true];
    $result = $collection->documents->search([...$params, ...$pins, 'filter_by' => 'id:=['.$important->id.','.$ordinary->id.'] && content_type:=Nutarimai']);
    expect(array_column(array_column($result['hits'], 'document'), 'id'))->toBe([(string) $ordinary->id]);
    $result = $collection->documents->search([...$params, ...$pins]);
    expect(array_column(array_column($result['hits'], 'document'), 'id'))->toBe([(string) $important->id, (string) $ordinary->id]);
    Cache::forever(SearchProfiles::cacheKey('documents'), SearchProfiles::VERSION);
    $match = $collection->documents->search([...SearchProfiles::all()['documents']['parameters'], 'q' => 'dokumentų', 'filter_by' => 'id:='.$important->id]);
    expect($match['found'])->toBe(1)
        ->and(collect($match['hits'][0]['highlights'])->firstWhere('field', 'title')['snippet'])->toContain('⟦dokumentas⟧');
});

test('tag attachment removal and renaming keep body owners searchable in both indexes', function (): void {
    usesTypesense();
    config(['scout.queue' => false]);
    $page = Page::factory()->create(['is_active' => true, 'lang' => 'lt']);
    $tag = Tag::factory()->create(['name' => ['lt' => 'Stipendijos', 'en' => 'Scholarships']]);
    $page->tags()->attach($tag);
    $client = app(Client::class);
    foreach ([Page::class, PublicPage::class] as $class) {
        expect($client->collections[(new $class)->searchableAs()]->documents[(string) $page->id]->retrieve()['tag_names'])->toContain('Stipendijos');
    }
    $tag->update(['name' => ['lt' => 'Parama', 'en' => 'Support']]);
    foreach ([Page::class, PublicPage::class] as $class) {
        expect($client->collections[(new $class)->searchableAs()]->documents[(string) $page->id]->retrieve()['tag_names'])->toBe(['Parama']);
    }
    $page->tags()->detach($tag);
    foreach ([Page::class, PublicPage::class] as $class) {
        expect($client->collections[(new $class)->searchableAs()]->documents[(string) $page->id]->retrieve()['tag_names'])->toBe([]);
    }
});

test('all published profiles and configured facet fields execute against the Typesense schemas', function (): void {
    usesTypesense();
    $client = app(Client::class);
    foreach (TypesenseCollectionConfig::getAllModelClasses() as $model) {
        $base = substr((new $model)->searchableAs(), strlen(config('scout.prefix')));
        $name = config('scout.prefix').'profile_validation_'.$base;
        $schema = config('scout.typesense.model-settings.'.$model.'.collection-schema');
        $client->collections->create(['name' => $name, ...$schema]);
        Cache::forever(SearchProfiles::cacheKey($base), SearchProfiles::VERSION);
        $profile = SearchProfiles::all()[$base];
        $result = $client->collections[$name]->documents->search([
            ...$profile['parameters'], 'q' => 'student', 'per_page' => 0,
            'sort_by' => '_text_match:desc,'.$profile['defaultSort'],
            'facet_by' => implode(',', $profile['facetFields']),
        ]);
        expect($result['found'])->toBe(0);
        foreach ($profile['sortFields'] as $field) {
            expect($client->collections[$name]->documents->search([
                ...$profile['parameters'], 'q' => '*', 'per_page' => 0, 'sort_by' => $field.':asc',
            ])['found'])->toBe(0);
        }
    }
});

test('facet counts and facet queries retain the scoped key and mandatory publication filter', function (): void {
    usesTypesense();
    config(['scout.queue' => false]);
    $tenant = Tenant::query()->first();
    $otherTenant = Tenant::factory()->create();
    $records = collect([
        News::factory()->create(['tenant_id' => $tenant->id, 'lang' => 'lt', 'draft' => false]),
        News::factory()->create(['tenant_id' => $tenant->id, 'lang' => 'en', 'draft' => false]),
        News::factory()->create(['tenant_id' => $tenant->id, 'lang' => 'en', 'draft' => true]),
        News::factory()->create(['tenant_id' => $otherTenant->id, 'lang' => 'en', 'draft' => false]),
    ]);
    $client = app(Client::class);
    $name = (new News)->searchableAs();
    $parent = $client->keys->create(['description' => 'scoped facet regression', 'actions' => ['documents:search'], 'collections' => [$name], 'expires_at' => now()->addHour()->timestamp]);
    $this->beforeApplicationDestroyed(fn () => $client->keys[$parent['id']]->delete());
    $key = $client->keys->generateScopedSearchKey($parent['value'], ['filter_by' => 'tenant_ids:=['.$tenant->id.']']);
    $scoped = new Client([...config('scout.typesense.client-settings'), 'api_key' => $key]);
    $base = 'draft:=false && id:=['.$records->pluck('id')->implode(',').']';
    $params = ['collection' => $name, 'q' => '*', 'query_by' => 'title', 'facet_by' => 'lang', 'per_page' => 0];
    $result = $scoped->multiSearch->perform(['searches' => [
        [...$params, 'filter_by' => $base.' && lang:=lt'],
        [...$params, 'filter_by' => $base],
        [...$params, 'filter_by' => $base, 'facet_query' => 'lang:en'],
    ]]);
    expect($result['results'][0]['found'])->toBe(1)
        ->and(collect($result['results'][1]['facet_counts'][0]['counts'])->pluck('count', 'value')->all())->toHaveCount(2)
        ->toMatchArray(['en' => 1, 'lt' => 1])
        ->and($result['results'][2]['facet_counts'][0]['counts'])->toHaveCount(1)
        ->and($result['results'][2]['facet_counts'][0]['counts'][0])->toMatchArray(['value' => 'en', 'count' => 1]);
});
