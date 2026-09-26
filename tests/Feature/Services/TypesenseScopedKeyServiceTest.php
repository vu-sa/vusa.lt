<?php

use App\Models\Meeting;
use App\Models\News;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseManager;
use App\Services\Typesense\TypesenseScopedKeyService;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
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
            ->and(TypesenseCollectionConfig::getPermissionForCollection('agenda_items'))->toBe('meetings.read.padalinys');

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
    test('super admin gets unrestricted keys for all collections', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $superAdmin = makeAdminUser();

        // Mock the Typesense client for this specific test
        $mockKeys = Mockery::mock(Keys::class);
        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->times(11) // Once for each admin collection (10) + 1 header key for multi_search
            ->andReturnUsing(function ($parentKey, $params) {
                // Super admin should NOT have filter_by in params
                expect($params)->not->toHaveKey('filter_by');
                expect($params)->toHaveKey('expires_at');

                return 'scoped-key-'.uniqid();
            });

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(ModelAuthorizer::class), app(InstitutionAccessService::class));

        $result = $service->generateScopedKeysForUser($superAdmin);

        expect($result)->toHaveKeys(['collections', 'header_key', 'expires_at', 'is_super_admin'])
            ->and($result['is_super_admin'])->toBeTrue();

        // Super admin should have access to all admin collections
        expect($result['collections'])->toHaveKeys(['meetings', 'agenda_items', 'news', 'pages', 'calendar', 'institutions', 'documents', 'resources', 'duties', 'users']);

        foreach ($result['collections'] as $collection) {
            expect($collection['has_access'])->toBeTrue()
                ->and($collection['tenant_ids'])->toBeEmpty();
        }
    });

    test('user with tenant permission gets scoped keys with tenant filter', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeTenantUserWithRole('Communication Coordinator', $tenant);

        // Give the role the necessary permission
        $duty = $user->duties()->first();
        $duty->givePermissionTo('meetings.read.padalinys');

        $mockKeys = Mockery::mock(Keys::class);
        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->andReturnUsing(function ($parentKey, $params) use ($tenant) {
                // User with tenant access should have filter_by with tenant_ids
                if (isset($params['filter_by'])) {
                    expect($params['filter_by'])->toContain('tenant_ids:=')
                        ->toContain((string) $tenant->id);
                }

                return 'scoped-key-'.uniqid();
            });

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(ModelAuthorizer::class), app(InstitutionAccessService::class));

        // Clear cache for this user first
        TypesenseScopedKeyService::invalidateForUser($user->id);

        $result = $service->generateScopedKeysForUser($user);

        expect($result['is_super_admin'])->toBeFalse();

        // Check that collections with tenant filtering include the tenant's ID
        // Skip 'documents' and 'resources' since they have no tenant filtering
        foreach ($result['collections'] as $name => $collection) {
            if ($collection['has_access'] && ! in_array($name, ['documents', 'resources'])) {
                expect($collection['tenant_ids'])->toContain($tenant->id);
            }
        }
    });

    test('user without permission gets only public collections', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeUser($tenant);

        // Do not give any roles or permissions

        $mockKeys = Mockery::mock(Keys::class);
        // Header key + documents + resources + public meetings and agenda items
        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->times(5)
            ->andReturn('scoped-key');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(ModelAuthorizer::class), app(InstitutionAccessService::class));

        // Clear cache
        TypesenseScopedKeyService::invalidateForUser($user->id);

        $result = $service->generateScopedKeysForUser($user);

        expect($result['is_super_admin'])->toBeFalse();
        // User without permissions still gets documents and resources (accessible to all authenticated users)
        expect($result['collections'])->toHaveKey('documents');
        expect($result['collections']['documents']['has_access'])->toBeTrue()
            ->and($result['collections'])->toHaveKey('resources')
            ->and($result['collections']['resources']['has_access'])->toBeTrue();
        // Public meetings are open to everyone; other permission-protected collections are not
        expect($result['collections'])->toHaveKey('meetings');
        expect($result['collections'])->not->toHaveKey('news');
    });

    test('keys are cached per user', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $superAdmin = makeAdminUser();

        $mockKeys = Mockery::mock(Keys::class);
        // Should only be called once due to caching
        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->times(11) // 10 admin collections + 1 header key, first call only
            ->andReturn('scoped-key-cached');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(ModelAuthorizer::class), app(InstitutionAccessService::class));

        // Clear cache first
        TypesenseScopedKeyService::invalidateForUser($superAdmin->id);

        // First call - should generate keys
        $result1 = $service->generateScopedKeysForUser($superAdmin);

        // Second call - should use cache
        $result2 = $service->generateScopedKeysForUser($superAdmin);

        expect($result1)->toEqual($result2);
    });

    test('cache is invalidated properly', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $superAdmin = makeAdminUser();
        $cacheKey = TypesenseScopedKeyService::getCacheKey($superAdmin->id);

        // Set something in cache
        cache()->put($cacheKey, ['test' => 'data'], 3600);
        expect(cache()->has($cacheKey))->toBeTrue();

        // Invalidate
        TypesenseScopedKeyService::invalidateForUser($superAdmin->id);

        // Should be gone
        expect(cache()->has($cacheKey))->toBeFalse();
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

    test('skip_tenant_filter collections get keys without filter_by', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeTenantUserWithRole('Communication Coordinator', $tenant);

        // User doesn't need any specific permission for documents since permission is null
        // Documents are accessible to all authenticated users

        $mockKeys = Mockery::mock(Keys::class);
        $unfilteredKeyCount = 0;

        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->andReturnUsing(function ($parentKey, $params) use (&$unfilteredKeyCount) {
                // Documents and resources collections should NOT have filter_by since skip_tenant_filter is true
                if (! isset($params['filter_by'])) {
                    $unfilteredKeyCount++;
                }

                return 'scoped-key-'.uniqid();
            });

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(ModelAuthorizer::class), app(InstitutionAccessService::class));

        TypesenseScopedKeyService::invalidateForUser($user->id);

        $result = $service->generateScopedKeysForUser($user);

        // Documents and resources should be in the result for any authenticated user (no permission required)
        expect($result['collections'])->toHaveKeys(['documents', 'resources']);
        expect($unfilteredKeyCount)->toBe(2);
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

    test('getAdminFrontendConfig excludes collections without access', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeUser($tenant);

        // No permissions = should get empty collections

        // We need to mock the TypesenseScopedKeyService
        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateScopedKeysForUser')
                ->once()
                ->andReturn([
                    'collections' => [], // No access to any collections
                    'expires_at' => time() + 3600,
                    'is_super_admin' => false,
                ]);
        });

        $config = TypesenseManager::getAdminFrontendConfig($user);

        expect($config)->toHaveKeys(['collections', 'expiresAt', 'isSuperAdmin', 'nodes'])
            ->and($config['collections'])->toBeEmpty()
            ->and($config['isSuperAdmin'])->toBeFalse();
    });

    test('getAdminFrontendConfig includes accessible collections for permitted user', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeTenantUserWithRole('Communication Coordinator', $tenant);

        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock) use ($tenant): void {
            $mock->shouldReceive('generateScopedKeysForUser')
                ->once()
                ->andReturn([
                    'collections' => [
                        'meetings' => [
                            'key' => 'scoped-key-meetings',
                            'tenant_ids' => [$tenant->id],
                            'has_access' => true,
                        ],
                        'agenda_items' => [
                            'key' => 'scoped-key-agenda',
                            'tenant_ids' => [$tenant->id],
                            'has_access' => true,
                        ],
                    ],
                    'expires_at' => time() + 3600,
                    'is_super_admin' => false,
                ]);
        });

        $config = TypesenseManager::getAdminFrontendConfig($user);

        expect($config['collections'])->toHaveKeys(['meetings', 'agenda_items'])
            ->and($config['collections']['meetings']['hasAccess'])->toBeTrue()
            ->and($config['collections']['meetings']['tenantIds'])->toContain($tenant->id);
    });
});

describe('Search API endpoints', function (): void {
    test('authenticated user can fetch search config', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeAdminUser($tenant);

        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateScopedKeysForUser')
                ->once()
                ->andReturn([
                    'collections' => [
                        'meetings' => [
                            'key' => 'test-key',
                            'tenant_ids' => [],
                            'has_access' => true,
                        ],
                    ],
                    'expires_at' => time() + 3600,
                    'is_super_admin' => true,
                ]);
        });

        $response = asUser($user)->get('/api/v1/admin/search/config');

        $response->assertOk()
            ->assertJsonStructure([
                'collections',
                'expiresAt',
                'isSuperAdmin',
                'nodes',
            ]);
    });

    test('unauthenticated user cannot fetch search config', function (): void {
        $response = $this->get('/api/v1/admin/search/config');

        // API routes may redirect to login (302) or return 401
        expect($response->status())->toBeIn([302, 401]);
    });

    test('refresh key endpoint works for authenticated user', function (): void {
        $this->markTestSkipped('Requires running Typesense server');
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeAdminUser($tenant);

        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateScopedKeysForUser')
                ->once()
                ->andReturn([
                    'collections' => [],
                    'expires_at' => time() + 3600,
                    'is_super_admin' => true,
                ]);
        });

        $response = asUser($user)->post('/api/v1/admin/search/refresh-key');

        $response->assertOk()
            ->assertJsonStructure(['success', 'config']);
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

        return (new TypesenseScopedKeyService($client, app(ModelAuthorizer::class), app(InstitutionAccessService::class)))
            ->generateScopedKeysForUser($user)['collections'];
    }

    beforeEach(function (): void {
        $this->publicType = Type::factory()->create();
        app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$this->publicType->id]])->save();
    });

    test('a member without permissions gets public meetings, agenda items and active institutions only', function (): void {
        $collections = keysFor(makeUser(Tenant::query()->first()));
        $publicTypes = "institution_type_ids:=[{$this->publicType->id}]";

        expect(scopedKeyFilter($collections['meetings']['key']))->toBe($publicTypes)
            ->and(scopedKeyFilter($collections['agenda_items']['key']))->toBe($publicTypes)
            ->and(scopedKeyFilter($collections['institutions']['key']))->toBe('is_active:=true');
    });

    test('a rep holding meetings.read.own also gets their own institutions', function (): void {
        $user = makeTenantUserWithRole('Student Representative', Tenant::query()->first());
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

    test('with no public meeting types a member still gets the collection, empty', function (): void {
        app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => []])->save();

        expect(scopedKeyFilter(keysFor(makeUser(Tenant::query()->first()))['meetings']['key']))->toBe('tenant_ids:=-1');
    });

    test('a settings change retires every cached key, with no reindex', function (): void {
        $user = makeUser(Tenant::query()->first());
        keysFor($user);

        $otherType = Type::factory()->create();
        app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$otherType->id]])->save();

        expect(scopedKeyFilter(keysFor($user)['meetings']['key']))->toBe("institution_type_ids:=[{$otherType->id}]");
    });

    test('a key expires when one of the user\'s duties ends, not an hour later', function (): void {
        $user = makeTenantUserWithRole('Student Representative', Tenant::query()->first());
        $duty = $user->duties()->first();
        // The end date is the last day in office, so today's date ends access at midnight.
        $duty->pivot->end_date = now()->toDateString();
        $duty->pivot->save();
        $this->travelTo(now()->endOfDay()->subMinutes(10));

        $expiresAt = scopedKeyParams(keysFor($user)['meetings']['key'])['expires_at'];

        expect($expiresAt)->toBeLessThanOrEqual(now()->addMinutes(11)->timestamp);
    });
});
