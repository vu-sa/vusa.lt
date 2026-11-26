<?php

use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
use App\Services\Typesense\SearchProfiles;
use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseManager;
use App\Services\Typesense\TypesenseScopedKeyService;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Typesense\Client;
use Typesense\Keys;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Configure Typesense settings for these tests
    config([
        'scout.typesense.client-settings.api_key' => 'test-admin-key',
        'scout.typesense.client-settings.search_only_key' => 'test-search-only-key',
        'scout.typesense.client-settings.admin_search_key' => 'test-admin-search-key',
        'scout.typesense.client-settings.nodes' => [
            [
                'host' => 'localhost',
                'port' => '8108',
                'protocol' => 'http',
                'path' => '',
            ],
        ],
        'scout.prefix' => 'test_',
    ]);
});

describe('TypesenseCollectionConfig', function (): void {
    test('returns correct public collection names with prefix', function (): void {
        $collections = TypesenseCollectionConfig::getPublicCollectionNames();

        // News/pages have separate admin (news/pages) and public (public_news/public_pages)
        // collections — the public ones carry the publish-status gating.
        expect($collections)->toContain('test_public_news')
            ->toContain('test_public_pages')
            ->toContain('test_documents')
            ->toContain('test_calendar')
            ->toContain('test_public_institutions')
            ->toContain('test_public_meetings');
    });

    test('returns correct admin collection names with prefix', function (): void {
        $collections = TypesenseCollectionConfig::getAdminCollectionNames();

        expect($collections)->toContain('test_meetings')
            ->toContain('test_agenda_items')
            ->toContain('test_news')
            ->toContain('test_pages')
            ->toContain('test_calendar')
            ->toContain('test_institutions')
            ->toContain('test_documents')
            ->toContain('test_resources');
    });

    test('returns correct permission for admin collections', function (): void {
        expect(TypesenseCollectionConfig::getPermissionForCollection('meetings'))
            ->toBe('meetings.read.padalinys')
            ->and(TypesenseCollectionConfig::getPermissionForCollection('agenda_items'))->toBe('agendaItems.read.padalinys');

        // News is now also an admin collection with its own permission
        expect(TypesenseCollectionConfig::getPermissionForCollection('news'))
            ->toBe('news.read.padalinys');

        // Documents are publicly accessible - no permission required
        expect(TypesenseCollectionConfig::getPermissionForCollection('documents'))
            ->toBeNull();
        expect(TypesenseCollectionConfig::shouldSkipTenantFilter('documents'))
            ->toBeTrue();

        // Resources are visible to all authenticated users across tenants
        expect(TypesenseCollectionConfig::getPermissionForCollection('resources'))
            ->toBeNull();
        expect(TypesenseCollectionConfig::shouldSkipTenantFilter('resources'))
            ->toBeTrue();
    });

    test('correctly identifies public vs admin collections', function (): void {
        // News is admin-only now — public_news is its separate, publication-gated
        // sibling collection. This split is what makes drafts/scheduled articles
        // findable in admin search without leaking into public search.
        expect(TypesenseCollectionConfig::isPublicCollection('news'))->toBeFalse()
            ->and(TypesenseCollectionConfig::isAdminCollection('news'))->toBeTrue()
            ->and(TypesenseCollectionConfig::isPublicCollection('public_news'))->toBeTrue()
            ->and(TypesenseCollectionConfig::isAdminCollection('public_news'))->toBeFalse();

        expect(TypesenseCollectionConfig::isPublicCollection('meetings'))->toBeFalse()
            ->and(TypesenseCollectionConfig::isAdminCollection('meetings'))->toBeTrue();
    });

    test('returns all model classes', function (): void {
        $models = TypesenseCollectionConfig::getAllModelClasses();

        expect($models)->toContain(News::class)
            ->toContain(Meeting::class)
            ->toContain(AgendaItem::class);
    });
});

describe('TypesenseScopedKeyService', function (): void {
    test('signs scoped keys with the public search key when the admin search key is blank', function (string $adminSearchKey): void {
        config(['scout.typesense.client-settings.admin_search_key' => $adminSearchKey]);
        $client = new Client([
            'api_key' => 'test-admin-key',
            'nodes' => config('scout.typesense.client-settings.nodes'),
        ]);
        $service = new TypesenseScopedKeyService($client, app(ModelAuthorizer::class), app(InstitutionAccessService::class));

        $result = $service->generateScopedKeysForUser(makeAdminUser());
        $keys = [$result['header_key'], ...array_column($result['collections'], 'key')];

        foreach ($keys as $key) {
            expect($key)->toBe($client->keys->generateScopedSearchKey('test-search-only-key', scopedKeyParams($key)));
        }
    })->with(['empty' => '', 'whitespace' => '   ']);

    test('a super admin gets unfiltered keys for every admin collection', function (): void {
        $collections = keysFor(makeAdminUser());

        expect(array_keys($collections))->toEqualCanonicalizing(array_keys(TypesenseCollectionConfig::getAdminCollections()));

        foreach ($collections as $collection) {
            expect($collection['has_access'])->toBeTrue();
            expect(scopedKeyParams($collection['key']))->not->toHaveKey('filter_by');
        }
    });

    test('getAdminCollections returns prefixed collection names', function (): void {
        $collections = TypesenseScopedKeyService::getAdminCollections();

        expect($collections)->toContain('test_meetings')
            ->toContain('test_agenda_items')
            ->toContain('test_news')
            ->toContain('test_pages')
            ->toContain('test_calendar')
            ->toContain('test_institutions')
            ->toContain('test_documents')
            ->toContain('test_resources')
            ->toContain('test_duties')
            ->toContain('test_users');
    });

});

describe('TypesenseManager', function (): void {
    test('getFrontendConfig returns public collections from centralized config', function (): void {
        $config = TypesenseManager::getFrontendConfig();

        expect($config)->toHaveKeys(['apiKey', 'nodes', 'collections']);

        // Check that all public collections are included
        expect($config['collections'])->toHaveKeys(['public_news', 'public_pages', 'documents', 'calendar', 'public_institutions', 'public_meetings']);

        // Admin collections should NOT be in public config
        expect($config['collections'])->not->toHaveKey('meetings')
            ->not->toHaveKey('agenda_items');
    });

});

describe('Search API endpoints', function (): void {

    test('authenticated members get baseline collections without a news key', function (): void {
        $user = User::factory()->create();

        $config = asUser($user)->getJson(route('api.v1.admin.search.config'))
            ->assertOk()
            ->assertJsonPath('isSuperAdmin', false)
            ->json('collections');

        expect($config)->toHaveKeys(['meetings', 'documents', 'resources'])
            ->not->toHaveKey('news');
        expect(scopedKeyParams($config['documents']['key']))->not->toHaveKey('filter_by');
        expect(scopedKeyParams($config['resources']['key']))->not->toHaveKey('filter_by');
    });

    test('unauthenticated user cannot fetch search config', function (): void {
        $this->getJson('/api/v1/admin/search/config')->assertUnauthorized();
    });

});

describe('public records in every scoped key', function (): void {
    /** The params a scoped key carries: HMAC digest (44 chars), parent key prefix (4), then the JSON. */
    function scopedKeyParams(string $scopedKey): array
    {
        return json_decode(substr(base64_decode($scopedKey), 48), true);
    }

    function scopedKeyFilter(string $scopedKey): ?string
    {
        return scopedKeyParams($scopedKey)['filter_by'] ?? null;
    }

    function keysFor(User $user): array
    {
        // Scoped keys are an HMAC computed locally, so a real client needs no running server.
        $client = new Client([
            'api_key' => 'test-admin-key',
            'nodes' => config('scout.typesense.client-settings.nodes'),
        ]);

        return new TypesenseScopedKeyService($client, app(ModelAuthorizer::class), app(InstitutionAccessService::class))
            ->generateScopedKeysForUser($user)['collections'];
    }

    beforeEach(function (): void {
        $this->publicType = InstitutionType::factory()->create();
        app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$this->publicType->id]])->save();
    });

    test('a member without permissions gets public meetings, agenda items, active institutions and their own', function (): void {
        $user = makeUser(Tenant::query()->first());
        $institutionId = $user->duties()->first()->institution_id;
        $collections = keysFor($user);
        $publicTypes = "institution_type_ids:=[{$this->publicType->id}]";

        expect(scopedKeyFilter($collections['meetings']['key']))->toBe('('.$publicTypes.' || institution_ids:=[`'.$institutionId.'`])')
            ->and(scopedKeyFilter($collections['agenda_items']['key']))->toBe('(institution_ids:=[`'.$institutionId.'`] || ('.$publicTypes.' && is_private:=false))')
            ->and(scopedKeyFilter($collections['institutions']['key']))->toBe('(is_active:=true || institution_ids:=[`'.$institutionId.'`])');
    });

    test('a representative finds their institutions without a separate own read grant', function (): void {
        $user = makeTenantUserWithRole('Studentų atstovas', Tenant::query()->first());
        $institutionId = $user->duties()->first()->institution_id;

        $filter = scopedKeyFilter(keysFor($user)['meetings']['key']);

        expect($filter)->toStartWith("(institution_type_ids:=[{$this->publicType->id}] || ")
            ->toContain("`{$institutionId}`");
    });

    test('meetings.read.padalinys adds the tenant clause', function (): void {
        $tenant = Tenant::query()->first();
        $user = makeUser($tenant);
        $duty = $user->duties()->first();
        $duty->pivot->end_date = null;
        $duty->pivot->save();
        $duty->givePermissionTo('meetings.read.padalinys');

        expect(scopedKeyFilter(keysFor($user)['meetings']['key']))
            ->toContain("institution_type_ids:=[{$this->publicType->id}]")
            ->toContain("tenant_ids:=[{$tenant->id}]");
    });

    test('with no public meeting types a member still gets their own institutions', function (): void {
        app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => []])->save();

        $user = makeUser(Tenant::query()->first());
        $institutionId = $user->duties()->first()->institution_id;

        expect(scopedKeyFilter(keysFor($user)['meetings']['key']))->toBe('institution_ids:=[`'.$institutionId.'`]');
    });

    test('every issued key carries the full embedded parameter set', function (): void {
        $user = makeUser(Tenant::query()->first());
        $client = new Client(['api_key' => 'test-admin-key', 'nodes' => config('scout.typesense.client-settings.nodes')]);
        $result = new TypesenseScopedKeyService($client, app(ModelAuthorizer::class), app(InstitutionAccessService::class))
            ->generateScopedKeysForUser($user);

        $keys = [$result['header_key'], ...array_column($result['collections'], 'key')];

        foreach ($keys as $key) {
            expect(scopedKeyParams($key))->toHaveKey('filter_curated_hits', true);
        }
    });

    test('a settings change retires every cached key, with no reindex', function (): void {
        $user = makeUser(Tenant::query()->first());
        keysFor($user);

        $otherType = InstitutionType::factory()->create();
        app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$otherType->id]])->save();

        expect(scopedKeyFilter(keysFor($user)['meetings']['key']))->toContain("institution_type_ids:=[{$otherType->id}]");
    });

    test('a key expires when one of the user\'s duties ends before its usual lifetime', function (): void {
        $user = makeTenantUserWithRole('Studentų atstovas', Tenant::query()->first());
        $duty = $user->duties()->first();
        // The end date is the last day in office, so today's date ends access at midnight.
        $duty->pivot->end_date = now()->toDateString();
        $duty->pivot->save();
        $this->travelTo(now()->endOfDay()->subMinutes(10));

        $expiresAt = scopedKeyParams(keysFor($user)['meetings']['key'])['expires_at'];

        expect($expiresAt)->toBeLessThanOrEqual(now()->addMinutes(11)->timestamp);
    });

    test('keys last fifteen minutes and leave the cache before they expire', function (): void {
        $this->freezeTime();
        $user = User::factory()->create();
        $keys = keysFor($user);
        $cacheKey = TypesenseScopedKeyService::getCacheKey($user->id);

        expect(TypesenseScopedKeyService::KEY_EXPIRY)->toBe(900)
            ->and(scopedKeyParams($keys['institutions']['key'])['expires_at'])->toBe(now()->addMinutes(15)->timestamp)
            ->and(cache()->has($cacheKey))->toBeTrue();

        $this->travel(801)->seconds();

        expect(cache()->has($cacheKey))->toBeFalse()
            ->and(scopedKeyParams($keys['institutions']['key'])['expires_at'])->toBeGreaterThan(now()->timestamp);
    });

    test('baseline institution keys withhold activity status and its facet', function (): void {
        $user = User::factory()->create();
        $params = scopedKeyParams(keysFor($user)['institutions']['key']);

        expect($params['exclude_fields'])->toBe('activity_status,'.SearchProfiles::EXCLUDED_FIELDS)
            ->and(explode(',', $params['facet_by']))->not->toContain('activity_status')
            ->and(TypesenseManager::getAdminFrontendConfig($user)['collections']['institutions']['excludedFields'])->toBe(['activity_status']);
    });

    test('refreshing keys replaces the cached expiry with a fresh fifteen minute lifetime', function (): void {
        $this->freezeTime();
        $user = makeUser(Tenant::query()->first());
        $oldExpiry = scopedKeyParams(keysFor($user)['institutions']['key'])['expires_at'];
        $this->travel(2)->minutes();

        $response = $this->actingAs($user)->postJson(route('api.v1.admin.search.refreshKey'));

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('config.expiresAt', now()->addMinutes(15)->timestamp);
        expect(cache()->get(TypesenseScopedKeyService::getCacheKey($user->id))['expires_at'])
            ->toBeGreaterThan($oldExpiry);
    });

    test('tenant institution readers still withhold activity status from public rows', function (): void {
        $user = makeUser(Tenant::query()->first());
        $user->givePermissionTo('institutions.read.padalinys');

        expect(scopedKeyParams(keysFor($user)['institutions']['key'])['exclude_fields'])->toBe('activity_status,'.SearchProfiles::EXCLUDED_FIELDS);
    });

    test('global institution readers receive activity status', function (): void {
        $user = User::factory()->create();
        $user->givePermissionTo('institutions.read.*');
        $params = scopedKeyParams(keysFor($user)['institutions']['key']);

        expect($params)->not->toHaveKey('exclude_fields')->not->toHaveKey('facet_by')
            ->and($params['filter_by'])->toBe('id:!=__no_access__')
            ->and(TypesenseManager::getAdminFrontendConfig($user)['collections']['institutions']['excludedFields'])->toBe([]);
    });
    test('global readers of tenant-scoped collections get keys without a tenant filter', function (): void {
        $user = User::factory()->create();
        $user->givePermissionTo(['users.read.*', 'news.read.*']);
        $padalinys = makeUser(Tenant::query()->first());
        $padalinys->givePermissionTo('users.read.padalinys');
        $keys = keysFor($user);

        expect(scopedKeyParams($keys['users']['key']))->not->toHaveKey('filter_by')
            ->and(scopedKeyParams($keys['news']['key']))->not->toHaveKey('filter_by')
            ->and($keys['users']['scope'])->toBe('all')
            ->and(scopedKeyFilter(keysFor($padalinys)['users']['key']))->toStartWith('tenant_ids:=[');
    });

    test('a cached key at its expiry is issued again rather than returned', function (): void {
        $this->freezeTime();
        $user = User::factory()->create();
        keysFor($user);
        $cacheKey = TypesenseScopedKeyService::getCacheKey($user->id);
        cache()->put($cacheKey, [...cache()->get($cacheKey), 'expires_at' => now()->timestamp], 60);

        expect(app(TypesenseScopedKeyService::class)->generateScopedKeysForUser($user)['expires_at'])->toBe(now()->addMinutes(15)->timestamp);
    });

    test('scoped keys count as configured with only the admin search key', function (): void {
        config(['scout.typesense.client-settings.search_only_key' => null]);
        expect(TypesenseScopedKeyService::isConfigured())->toBeTrue();

        config(['scout.typesense.client-settings.admin_search_key' => null]);
        expect(TypesenseScopedKeyService::isConfigured())->toBeFalse();
    });
});
