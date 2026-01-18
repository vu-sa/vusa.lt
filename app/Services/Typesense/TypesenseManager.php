<?php

namespace App\Services\Typesense;

use App\Models\User;
use Illuminate\Support\Facades\Config;

class TypesenseManager
{
    /**
     * Get the frontend configuration for Typesense (public search)
     */
    public static function getFrontendConfig(): array
    {
        if (! self::isConfigured()) {
            return [];
        }

        $typesenseConfig = Config::get('scout.typesense.client-settings');
        $nodes = $typesenseConfig['nodes'] ?? [];
        $prefix = Config::get('scout.prefix', '');

        // Build collections map from centralized config
        $collections = [];
        foreach (TypesenseCollectionConfig::getPublicCollectionBaseNames() as $name) {
            $collections[$name] = $prefix.$name;
        }

        return [
            'apiKey' => Config::get('scout.typesense.client-settings.search_only_key', $typesenseConfig['api_key']),
            'nodes' => array_map(function ($node) {
                // Replace Docker service name with localhost for frontend access
                $host = $node['host'] === 'typesense' ? 'localhost' : $node['host'];

                return [
                    'host' => $host,
                    'port' => (int) $node['port'],
                    'protocol' => $node['protocol'],
                ];
            }, $nodes),
            'collections' => $collections,
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

    /**
     * Get the admin frontend configuration for Typesense with per-collection scoped API keys
     *
     * This returns user-specific scoped API keys per collection, each with embedded
     * tenant filtering based on the user's permissions for that specific collection.
     * This allows different access levels per collection (e.g., all tenants for resources,
     * specific tenants for meetings).
     *
     * @param  User  $user  The authenticated user
     * @return array{collections: array<string, array{key: string, name: string, tenantIds: int[], hasAccess: bool}>, expiresAt: int, isSuperAdmin: bool, nodes: array}
     */
    public static function getAdminFrontendConfig(User $user): array
    {
        if (! self::isConfigured()) {
            return [];
        }

        $typesenseConfig = Config::get('scout.typesense.client-settings');
        $nodes = $typesenseConfig['nodes'] ?? [];
        $prefix = config('scout.prefix', '');

        // Generate per-collection scoped keys for user
        $scopedKeyService = app(TypesenseScopedKeyService::class);
        $scopedKeysData = $scopedKeyService->generateScopedKeysForUser($user);

        // Transform collections data for frontend consumption
        $collections = [];
        foreach ($scopedKeysData['collections'] as $collection => $data) {
            $collections[$collection] = [
                'key' => $data['key'],
                'name' => $prefix.$collection,
                'tenantIds' => $data['tenant_ids'],
                'institutionIds' => $data['institution_ids'] ?? [],
                'directInstitutionIds' => $data['direct_institution_ids'] ?? [],
                'scope' => $data['scope'] ?? 'none',
                'hasAccess' => $data['has_access'],
            ];
        }

        return [
            'collections' => $collections,
            'headerKey' => $scopedKeysData['header_key'] ?? '',
            'expiresAt' => $scopedKeysData['expires_at'],
            'isSuperAdmin' => $scopedKeysData['is_super_admin'],
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
     * Get scoped key expiry information for a user
     * Useful for frontend to know when to refresh the key
     */
    public static function getScopedKeyExpiryInfo(User $user): array
    {
        $scopedKeyService = app(TypesenseScopedKeyService::class);
        $scopedKeysData = $scopedKeyService->generateScopedKeysForUser($user);

        return [
            'expiresAt' => $scopedKeysData['expires_at'],
            'expiresIn' => $scopedKeysData['expires_at'] - time(),
        ];
    }
}
