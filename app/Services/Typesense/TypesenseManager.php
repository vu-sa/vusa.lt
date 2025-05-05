<?php

namespace App\Services\Typesense;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class TypesenseManager
{
    /**
     * Get the frontend configuration for Typesense
     * 
     * Only exposes the necessary information for the frontend client,
     * using a search-only API key
     * 
     * @return array
     */
    public static function getFrontendConfig(): array
    {
        // Use a search-only API key for frontend operations
        $searchOnlyApiKey = env('TYPESENSE_SEARCH_ONLY_KEY', null);
        
        // If no search-only key is set, use cache to dynamically generate one
        if (!$searchOnlyApiKey) {
            $searchOnlyApiKey = self::getOrCreateSearchOnlyKey();
        }
        
        $typesenseConfig = Config::get('scout.typesense.client-settings');
        $nodes = $typesenseConfig['nodes'] ?? [];
        
        // Filter only needed fields for frontend
        $frontendConfig = [
            'apiKey' => $searchOnlyApiKey,
            'nodes' => array_map(function ($node) {
                return [
                    'host' => $node['host'],
                    'port' => $node['port'],
                    'protocol' => $node['protocol'],
                    'path' => $node['path'] ?? '',
                ];
            }, $nodes),
            'searchParams' => [
                'query_by' => [
                    'documents' => 'title,summary',
                    'pages' => 'title,content',
                    'news' => 'title,short',
                ],
                'num_typos' => 1
            ],
        ];
        
        return $frontendConfig;
    }
    
    /**
     * Get or create a search-only API key for Typesense
     * 
     * @return string
     */
    protected static function getOrCreateSearchOnlyKey(): string
    {
        // Cache the search-only key to avoid excessive API calls to Typesense
        return Cache::remember('typesense_search_only_key', now()->addDays(7), function () {
            // Get the admin client
            $client = app(\Typesense\Client::class);
            
            try {
                // Create a search-only API key with restricted permissions
                $response = $client->keys->create([
                    'description' => 'Search-only API key for frontend use',
                    'actions' => ['documents:search'],
                    'collections' => ['documents', 'pages', 'news'],
                    // Optional: Expire in 7 days, then we'll regenerate
                    'expires_at' => time() + (7 * 24 * 60 * 60)
                ]);
                
                return $response['value'] ?? '';
            } catch (\Exception $e) {
                // If key creation fails, use a default from .env or config as fallback
                report($e);
                return env('TYPESENSE_SEARCH_ONLY_KEY_FALLBACK', 'xyz');
            }
        });
    }
}