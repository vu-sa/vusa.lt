<?php

use App\Models\User;
use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseManager;
use App\Services\Typesense\TypesenseScopedKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'scout.typesense.client-settings.api_key' => 'admin-key',
        'scout.typesense.client-settings.search_only_key' => 'search-only-key',
        'scout.typesense.client-settings.admin_search_key' => 'admin-search-key',
        'scout.typesense.client-settings.nodes' => [
            ['host' => '127.0.0.1', 'port' => '8108', 'path' => '', 'protocol' => 'http'],
        ],
        'scout.typesense.public-node' => [
            'host' => 'search.vusa.lt',
            'port' => '443',
            'protocol' => 'https',
        ],
    ]);
});

describe('browser nodes', function () {
    test('public search gets the public node, not the internal one', function () {
        $node = TypesenseManager::getFrontendConfig()['nodes'][0];

        // The server indexes against 127.0.0.1; the browser must never be told that.
        expect($node['host'])->toBe('search.vusa.lt')
            ->and($node['port'])->toBe(443)
            ->and($node['protocol'])->toBe('https');
    });

    test('admin search gets the public node too', function () {
        // /mano/search runs in the browser just like public search does, but it is served
        // by a separate config method that used to build its nodes independently.
        // The user is only handed to the (mocked) key service, so it need not be persisted.
        $user = User::factory()->make();

        $this->mock(TypesenseScopedKeyService::class, function ($mock) {
            $mock->shouldReceive('generateScopedKeysForUser')->andReturn([
                'collections' => [],
                'header_key' => 'scoped-key',
                'expires_at' => now()->addHour()->timestamp,
                'is_super_admin' => false,
            ]);
        });

        $node = TypesenseManager::getAdminFrontendConfig($user)['nodes'][0];

        expect($node['host'])->toBe('search.vusa.lt')
            ->and($node['port'])->toBe(443)
            ->and($node['protocol'])->toBe('https');
    });

    test('falls back to the client node when no public node is set', function () {
        config(['scout.typesense.public-node' => ['host' => null, 'port' => null, 'protocol' => null]]);

        $node = TypesenseManager::getFrontendConfig()['nodes'][0];

        expect($node['host'])->toBe('127.0.0.1')
            ->and($node['port'])->toBe(8108);
    });

    test('the docker service name is never handed to the browser', function () {
        config([
            'scout.typesense.public-node' => ['host' => null, 'port' => null, 'protocol' => null],
            'scout.typesense.client-settings.nodes' => [
                ['host' => 'typesense', 'port' => '8108', 'path' => '', 'protocol' => 'http'],
            ],
        ]);

        expect(TypesenseManager::getFrontendConfig()['nodes'][0]['host'])->toBe('localhost');
    });

    test('the admin api key is never exposed to the browser', function () {
        $config = TypesenseManager::getFrontendConfig();

        expect($config['apiKey'])->toBe('search-only-key')
            ->and($config['apiKey'])->not->toBe('admin-key');
    });
});

describe('collection names', function () {
    test('shared collections are not listed twice', function () {
        $names = TypesenseCollectionConfig::getAllCollectionNames();

        // news, pages, calendar and documents are configured as both public and admin
        // collections, but there is only one Typesense collection per name.
        expect($names)->toBe(array_values(array_unique($names)));
    });

    test('still contains both public and admin collections', function () {
        $names = TypesenseCollectionConfig::getAllCollectionNames();

        expect($names)->toContain(...TypesenseCollectionConfig::getPublicCollectionNames())
            ->and($names)->toContain(...TypesenseCollectionConfig::getAdminCollectionNames());
    });
});
