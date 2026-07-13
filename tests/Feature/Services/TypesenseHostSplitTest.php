<?php

use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseManager;

beforeEach(function () {
    config([
        'scout.typesense.client-settings.api_key' => 'admin-key',
        'scout.typesense.client-settings.search_only_key' => 'search-only-key',
        'scout.typesense.client-settings.nodes' => [
            ['host' => '127.0.0.1', 'port' => '8108', 'path' => '', 'protocol' => 'http'],
        ],
    ]);
});

describe('browser nodes', function () {
    test('the browser gets the public node, not the internal one', function () {
        config([
            'scout.typesense.public-node' => [
                'host' => 'search.vusa.lt',
                'port' => '443',
                'protocol' => 'https',
            ],
        ]);

        $node = TypesenseManager::getFrontendConfig()['nodes'][0];

        // The server indexes against 127.0.0.1; the browser must never be told that.
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
