<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseManager;
use App\Services\Typesense\TypesenseScopedKeyService;
use Mockery\MockInterface;
use Typesense\Client;
use Typesense\Keys;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
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

describe('TypesenseCollectionConfig', function () {
    test('returns correct public collection names with prefix', function () {
        $collections = TypesenseCollectionConfig::getPublicCollectionNames();

        expect($collections)->toContain('test_news')
            ->toContain('test_pages')
            ->toContain('test_documents')
            ->toContain('test_calendar')
            ->toContain('test_public_institutions')
            ->toContain('test_public_meetings');
    });

    test('returns correct admin collection names with prefix', function () {
        $collections = TypesenseCollectionConfig::getAdminCollectionNames();

        expect($collections)->toContain('test_meetings')
            ->toContain('test_agenda_items')
            ->toContain('test_news')
            ->toContain('test_pages')
            ->toContain('test_calendar')
            ->toContain('test_institutions')
            ->toContain('test_documents');
    });

    test('returns correct permission for admin collections', function () {
        expect(TypesenseCollectionConfig::getPermissionForCollection('meetings'))
            ->toBe('meetings.read.padalinys');

        expect(TypesenseCollectionConfig::getPermissionForCollection('agenda_items'))
            ->toBe('meetings.read.padalinys');

        // News is now also an admin collection with its own permission
        expect(TypesenseCollectionConfig::getPermissionForCollection('news'))
            ->toBe('news.read.padalinys');

        // Documents are publicly accessible - no permission required
        expect(TypesenseCollectionConfig::getPermissionForCollection('documents'))
            ->toBeNull();
        expect(TypesenseCollectionConfig::shouldSkipTenantFilter('documents'))
            ->toBeTrue();
    });

    test('correctly identifies public vs admin collections', function () {
        // News is in BOTH public and admin collections
        expect(TypesenseCollectionConfig::isPublicCollection('news'))->toBeTrue();
        expect(TypesenseCollectionConfig::isPublicCollection('meetings'))->toBeFalse();

        expect(TypesenseCollectionConfig::isAdminCollection('meetings'))->toBeTrue();
        // News is now also an admin collection
        expect(TypesenseCollectionConfig::isAdminCollection('news'))->toBeTrue();
    });

    test('returns all model classes', function () {
        $models = TypesenseCollectionConfig::getAllModelClasses();

        expect($models)->toContain(\App\Models\News::class)
            ->toContain(\App\Models\Meeting::class)
            ->toContain(\App\Models\Pivots\AgendaItem::class);
    });
});

describe('TypesenseScopedKeyService', function () {
    test('super admin gets unrestricted keys for all collections', function () {
        $superAdmin = makeAdminUser();

        // Mock the Typesense client
        $mockKeys = Mockery::mock(Keys::class);
        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->times(8) // Once for each admin collection (7) + 1 header key for multi_search
            ->andReturnUsing(function ($parentKey, $params) {
                // Super admin should NOT have filter_by in params
                expect($params)->not->toHaveKey('filter_by');
                expect($params)->toHaveKey('expires_at');

                return 'scoped-key-'.uniqid();
            });

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(\App\Services\ModelAuthorizer::class));

        $result = $service->generateScopedKeysForUser($superAdmin);

        expect($result)->toHaveKey('collections')
            ->toHaveKey('header_key')
            ->toHaveKey('expires_at')
            ->toHaveKey('is_super_admin');

        expect($result['is_super_admin'])->toBeTrue();

        // Super admin should have access to all admin collections
        expect($result['collections'])->toHaveKey('meetings')
            ->toHaveKey('agenda_items')
            ->toHaveKey('news')
            ->toHaveKey('pages')
            ->toHaveKey('calendar')
            ->toHaveKey('institutions')
            ->toHaveKey('documents');

        foreach ($result['collections'] as $collection) {
            expect($collection['has_access'])->toBeTrue();
            expect($collection['tenant_ids'])->toBe([]);
        }
    });

    test('user with tenant permission gets scoped keys with tenant filter', function () {
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
                    expect($params['filter_by'])->toContain('tenant_ids:=');
                    expect($params['filter_by'])->toContain((string) $tenant->id);
                }

                return 'scoped-key-'.uniqid();
            });

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(\App\Services\ModelAuthorizer::class));

        // Clear cache for this user first
        TypesenseScopedKeyService::invalidateForUser($user->id);

        $result = $service->generateScopedKeysForUser($user);

        expect($result['is_super_admin'])->toBeFalse();

        // Check that collections with tenant filtering include the tenant's ID
        // Skip 'documents' since it has no permission requirement and no tenant filtering
        foreach ($result['collections'] as $name => $collection) {
            if ($collection['has_access'] && $name !== 'documents') {
                expect($collection['tenant_ids'])->toContain($tenant->id);
            }
        }
    });

    test('user without permission gets only public collections', function () {
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeUser($tenant);

        // Do not give any roles or permissions

        $mockKeys = Mockery::mock(Keys::class);
        // Header key + documents key (documents is accessible to all authenticated users)
        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->times(2)
            ->andReturn('scoped-key');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(\App\Services\ModelAuthorizer::class));

        // Clear cache
        TypesenseScopedKeyService::invalidateForUser($user->id);

        $result = $service->generateScopedKeysForUser($user);

        expect($result['is_super_admin'])->toBeFalse();
        // User without permissions still gets documents (public collection)
        expect($result['collections'])->toHaveKey('documents');
        expect($result['collections']['documents']['has_access'])->toBeTrue();
        // But should NOT have access to permission-protected collections
        expect($result['collections'])->not->toHaveKey('meetings');
        expect($result['collections'])->not->toHaveKey('news');
    });

    test('keys are cached per user', function () {
        $superAdmin = makeAdminUser();

        $mockKeys = Mockery::mock(Keys::class);
        // Should only be called once due to caching
        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->times(8) // 7 admin collections + 1 header key, first call only
            ->andReturn('scoped-key-cached');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(\App\Services\ModelAuthorizer::class));

        // Clear cache first
        TypesenseScopedKeyService::invalidateForUser($superAdmin->id);

        // First call - should generate keys
        $result1 = $service->generateScopedKeysForUser($superAdmin);

        // Second call - should use cache
        $result2 = $service->generateScopedKeysForUser($superAdmin);

        expect($result1)->toEqual($result2);
    });

    test('cache is invalidated properly', function () {
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

    test('getAdminCollections returns prefixed collection names', function () {
        $collections = TypesenseScopedKeyService::getAdminCollections();

        expect($collections)->toContain('test_meetings')
            ->toContain('test_agenda_items')
            ->toContain('test_news')
            ->toContain('test_pages')
            ->toContain('test_calendar')
            ->toContain('test_institutions')
            ->toContain('test_documents');
    });

    test('skip_tenant_filter collections get keys without filter_by', function () {
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeTenantUserWithRole('Communication Coordinator', $tenant);

        // User doesn't need any specific permission for documents since permission is null
        // Documents are accessible to all authenticated users

        $mockKeys = Mockery::mock(Keys::class);
        $documentKeyGenerated = false;

        $mockKeys->shouldReceive('generateScopedSearchKey')
            ->andReturnUsing(function ($parentKey, $params) use (&$documentKeyGenerated) {
                // Documents collection should NOT have filter_by since skip_tenant_filter is true
                // We can't directly check which collection, but filter_by should be absent for documents
                if (! isset($params['filter_by'])) {
                    $documentKeyGenerated = true;
                }

                return 'scoped-key-'.uniqid();
            });

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getKeys')->andReturn($mockKeys);

        $service = new TypesenseScopedKeyService($mockClient, app(\App\Services\ModelAuthorizer::class));

        TypesenseScopedKeyService::invalidateForUser($user->id);

        $result = $service->generateScopedKeysForUser($user);

        // Documents should be in the result for any authenticated user (no permission required)
        expect($result['collections'])->toHaveKey('documents');
        expect($documentKeyGenerated)->toBeTrue();
    });
});

describe('TypesenseManager', function () {
    test('getFrontendConfig returns public collections from centralized config', function () {
        $config = TypesenseManager::getFrontendConfig();

        expect($config)->toHaveKey('apiKey')
            ->toHaveKey('nodes')
            ->toHaveKey('collections');

        // Check that all public collections are included
        expect($config['collections'])->toHaveKey('news')
            ->toHaveKey('pages')
            ->toHaveKey('documents')
            ->toHaveKey('calendar')
            ->toHaveKey('public_institutions')
            ->toHaveKey('public_meetings');

        // Admin collections should NOT be in public config
        expect($config['collections'])->not->toHaveKey('meetings')
            ->not->toHaveKey('agenda_items');
    });

    test('getAdminFrontendConfig excludes collections without access', function () {
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeUser($tenant);

        // No permissions = should get empty collections

        // We need to mock the TypesenseScopedKeyService
        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generateScopedKeysForUser')
                ->once()
                ->andReturn([
                    'collections' => [], // No access to any collections
                    'expires_at' => time() + 3600,
                    'is_super_admin' => false,
                ]);
        });

        $config = TypesenseManager::getAdminFrontendConfig($user);

        expect($config)->toHaveKey('collections')
            ->toHaveKey('expiresAt')
            ->toHaveKey('isSuperAdmin')
            ->toHaveKey('nodes');

        expect($config['collections'])->toBe([]);
        expect($config['isSuperAdmin'])->toBeFalse();
    });

    test('getAdminFrontendConfig includes accessible collections for permitted user', function () {
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeTenantUserWithRole('Communication Coordinator', $tenant);

        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock) use ($tenant) {
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

        expect($config['collections'])->toHaveKey('meetings')
            ->toHaveKey('agenda_items');

        expect($config['collections']['meetings']['hasAccess'])->toBeTrue();
        expect($config['collections']['meetings']['tenantIds'])->toContain($tenant->id);
    });
});

describe('Search API endpoints', function () {
    test('authenticated user can fetch search config', function () {
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeAdminUser($tenant);

        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock) {
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

    test('unauthenticated user cannot fetch search config', function () {
        $response = $this->get('/api/v1/admin/search/config');

        // API routes may redirect to login (302) or return 401
        expect($response->status())->toBeIn([302, 401]);
    });

    test('refresh key endpoint works for authenticated user', function () {
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);
        $user = makeAdminUser($tenant);

        $this->mock(TypesenseScopedKeyService::class, function (MockInterface $mock) {
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
