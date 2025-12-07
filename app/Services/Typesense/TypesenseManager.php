<?php

namespace App\Services\Typesense;

use Illuminate\Support\Facades\Config;

class TypesenseManager
{
    /**
     * Get the frontend configuration for Typesense
     */
    public static function getFrontendConfig(): array
    {
        if (! self::isConfigured()) {
            return [];
        }

        $typesenseConfig = Config::get('scout.typesense.client-settings');
        $nodes = $typesenseConfig['nodes'] ?? [];

        return [
            'apiKey' => config('scout.typesense.client-settings.search_only_key', $typesenseConfig['api_key']),
            'nodes' => array_map(function ($node) {
                // Replace Docker service name with localhost for frontend access
                $host = $node['host'] === 'typesense' ? 'localhost' : $node['host'];

                return [
                    'host' => $host,
                    'port' => (int) $node['port'],
                    'protocol' => $node['protocol'],
                ];
            }, $nodes),
        ];
    }

    /**
     * Check if Typesense is properly configured
     */
    public static function isConfigured(): bool
    {
        $apiKey = config('scout.typesense.client-settings.api_key');

        return ! empty($apiKey);
    }

    /**
     * Get a warning message if Typesense is not configured
     */
    public static function getConfigWarning(): ?string
    {
        if (! self::isConfigured()) {
            return 'Typesense API key is not configured. Search functionality will use database fallback. Configure TYPESENSE_API_KEY for full search features.';
        }

        return null;
    }

    /**
     * Get list of configured Typesense collections from model settings
     */
    public static function getCollections(): array
    {
        $modelSettings = Config::get('scout.typesense.model-settings', []);

        return array_keys($modelSettings);
    }
}
