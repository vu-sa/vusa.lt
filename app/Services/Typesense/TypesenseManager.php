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
        if (!self::isConfigured()) {
            return [];
        }
        
        $typesenseConfig = Config::get('scout.typesense.client-settings');
        $nodes = $typesenseConfig['nodes'] ?? [];
        
        return [
            'apiKey' => env('TYPESENSE_SEARCH_ONLY_KEY', $typesenseConfig['api_key']),
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
        $apiKey = env('TYPESENSE_API_KEY');
        return !empty($apiKey) && $apiKey !== 'xyz';
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
