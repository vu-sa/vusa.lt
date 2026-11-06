<?php

use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Typesense\TypesenseScopedKeyService;
use GuzzleHttp\Exception\ConnectException;
use Http\Client\Exception\NetworkException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Typesense\Client;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    usesTypesense();

    $this->client = app(Client::class);
    try {
        $this->client->health->retrieve();
    } catch (ConnectException|NetworkException) {
        $this->markTestSkipped('Requires running Typesense server');
    }

    $keys = $this->client->keys;
    $parent = $keys->create([
        'description' => 'scoped key test '.config('scout.prefix'),
        'actions' => ['documents:search'],
        'collections' => [config('scout.prefix').'.*'],
        'expires_at' => now()->addHour()->timestamp,
    ]);
    config([
        'scout.queue' => false,
        'scout.typesense.client-settings.admin_search_key' => $parent['value'],
        'scout.typesense.client-settings.search_only_key' => $parent['value'],
    ]);
    $this->beforeApplicationDestroyed(fn () => $keys[$parent['id']]->delete());
});

test('pinned results obey the user visibility filter', function (string $credential): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->for(Tenant::query()->first())->create(['is_active' => false]);
    $collection = $institution->searchableAs();
    expect($this->client->collections[$collection]->documents[$institution->id]->retrieve()['id'])->toBe((string) $institution->id);
    expect(Gate::forUser($user)->allows('viewSummary', $institution))->toBeFalse();

    $keys = app(TypesenseScopedKeyService::class)->generateScopedKeysForUser($user);
    $client = new Client([
        'api_key' => $credential === 'collection' ? $keys['collections']['institutions']['key'] : $keys['header_key'],
        'nodes' => config('scout.typesense.client-settings.nodes'),
    ]);
    $result = $client->multiSearch->perform(['searches' => [[
        'collection' => $collection,
        'q' => '*',
        'query_by' => 'name_lt,name_en',
        'filter_by' => 'id:=`'.$institution->id.'`',
        'pinned_hits' => $institution->id.':1',
        'filter_curated_hits' => false,
        ...($credential === 'per-search' ? ['x-typesense-api-key' => $keys['collections']['institutions']['key']] : []),
    ]]])['results'][0];

    expect($result)->not->toHaveKey('error');
    expect($result['found'])->toBe(0);
    expect($result['hits'])->toBe([]);
})->with(['transport', 'collection', 'per-search']);

test('baseline institution searches omit activity status while global readers receive it', function (): void {
    $baseline = User::factory()->create();
    $reader = User::factory()->create();
    $reader->givePermissionTo('institutions.read.*');
    $institution = Institution::factory()->for(Tenant::query()->first())->create(['is_active' => true]);
    $collection = $institution->searchableAs();
    $status = $this->client->collections[$collection]->documents[$institution->id]->retrieve()['activity_status'];

    foreach ([$baseline, $reader] as $user) {
        $keys = app(TypesenseScopedKeyService::class)->generateScopedKeysForUser($user);
        $client = new Client([
            'api_key' => $keys['collections']['institutions']['key'],
            'nodes' => config('scout.typesense.client-settings.nodes'),
        ]);
        $result = $client->collections[$collection]->documents->search([
            'q' => '*',
            'query_by' => 'name_lt,name_en',
            'filter_by' => 'id:=`'.$institution->id.'`',
            'include_fields' => 'id,activity_status',
            'exclude_fields' => '',
            'facet_by' => 'activity_status',
        ]);

        expect($result['found'])->toBe(1);
        if ($user->is($baseline)) {
            expect($result['hits'][0]['document'])->not->toHaveKey('activity_status');
            expect(array_column($result['facet_counts'], 'field_name'))->not->toContain('activity_status');
        } else {
            expect($result['hits'][0]['document']['activity_status'])->toBe($status);
            expect(array_column($result['facet_counts'], 'field_name'))->toContain('activity_status');
        }
    }
});

test('saving pronoun visibility updates the indexed values', function (): void {
    $user = User::factory()->create([
        'pronouns' => ['lt' => 'Jie/jų', 'en' => 'They/them'],
        'show_pronouns' => true,
    ]);
    $document = $this->client->collections[$user->searchableAs()]->documents[$user->id];
    expect($document->retrieve())->toMatchArray(['pronouns_lt' => 'Jie/jų', 'pronouns_en' => 'They/them']);

    $user->update(['show_pronouns' => false]);

    expect($document->retrieve())->toMatchArray(['pronouns_lt' => '', 'pronouns_en' => '']);

    $user->update(['show_pronouns' => true]);

    expect($document->retrieve())->toMatchArray(['pronouns_lt' => 'Jie/jų', 'pronouns_en' => 'They/them']);
});
