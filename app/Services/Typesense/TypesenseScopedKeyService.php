<?php

namespace App\Services\Typesense;

use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Typesense\Client;

/**
 * Service for generating and managing Typesense scoped API keys.
 *
 * Scoped API keys embed search restrictions (like tenant filtering) directly into the key,
 * making it impossible for users to bypass authorization by modifying search parameters.
 *
 * Per-Collection Keys:
 * - Each collection can have different tenant access based on user permissions
 * - E.g., user might have access to all tenants for resources but only specific tenants for meetings
 *
 * Cache Strategy:
 * - Keys are cached per-user with pattern "typesense_scoped_keys:{userId}"
 * - Cache TTL matches key expiry (1 hour by default)
 * - Cache is invalidated when user's duties/roles change via UserPermissionObserver
 *
 * @see https://typesense.org/docs/0.25.0/api/api-keys.html#generate-scoped-search-key
 */
class TypesenseScopedKeyService
{
    /**
     * Key expiry in seconds (1 hour)
     */
    public const KEY_EXPIRY = 3600;

    /**
     * Cache TTL - slightly less than key expiry to ensure fresh keys
     */
    protected const CACHE_TTL = 3500;

    protected Client $client;

    protected ModelAuthorizer $authorizer;

    protected InstitutionAccessService $institutionAccessService;

    public function __construct(Client $client, ModelAuthorizer $authorizer, InstitutionAccessService $institutionAccessService)
    {
        $this->client = $client;
        $this->authorizer = $authorizer;
        $this->institutionAccessService = $institutionAccessService;
    }

    /**
     * Generate scoped search keys for all collections for the given user.
     *
     * Returns a separate scoped key per collection, each with its own tenant filtering
     * based on the user's permissions for that specific collection.
     *
     * Collections the user has no access to are excluded from the response entirely,
     * rather than returning a key that would return empty results.
     *
     * @param  User  $user  The user to generate keys for
     * @return array{collections: array<string, array{key: string, tenant_ids: int[], has_access: bool}>, expires_at: int, is_super_admin: bool}
     */
    public function generateScopedKeysForUser(User $user): array
    {
        $cacheKey = self::getCacheKey($user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $this->buildScopedKeys($user);
        });
    }

    /**
     * Generate a single scoped search key for the given user (legacy method).
     *
     * @deprecated Use generateScopedKeysForUser() for per-collection keys
     *
     * @param  User  $user  The user to generate a key for
     * @return array{key: string, expires_at: int, tenant_ids: int[], is_super_admin: bool}
     */
    public function generateScopedKeyForUser(User $user): array
    {
        $keysData = $this->generateScopedKeysForUser($user);

        // Return the first collection's key for backwards compatibility
        $firstCollection = array_key_first($keysData['collections']);
        $firstCollectionData = $keysData['collections'][$firstCollection] ?? [];

        return [
            'key' => $firstCollectionData['key'] ?? '',
            'expires_at' => $keysData['expires_at'],
            'tenant_ids' => $firstCollectionData['tenant_ids'] ?? [],
            'is_super_admin' => $keysData['is_super_admin'],
        ];
    }

    /**
     * Build scoped keys for all collections with per-collection tenant filtering
     *
     * Collections without access are excluded from the response rather than
     * returning keys with impossible filters. This provides cleaner responses
     * and allows the frontend to clearly know which collections are available.
     *
     * Some collections (marked with skip_tenant_filter) are accessible without
     * tenant restrictions (e.g., documents are publicly searchable).
     *
     * @return array{collections: array<string, array{key: string, tenant_ids: int[], has_access: bool}>, expires_at: int, is_super_admin: bool}
     */
    protected function buildScopedKeys(User $user): array
    {
        $parentKey = Config::get('scout.typesense.client-settings.admin_search_key')
            ?? Config::get('scout.typesense.client-settings.search_only_key');
        $expiresAt = time() + self::KEY_EXPIRY;
        $prefix = config('scout.prefix', '');

        $this->authorizer->forUser($user);
        $isSuperAdmin = $user->isSuperAdmin();

        $collections = [];
        $adminCollections = TypesenseCollectionConfig::getAdminCollections();

        foreach ($adminCollections as $collection => $config) {
            $permission = $config['permission'] ?? null;
            $ownPermission = $config['own_permission'] ?? null;
            $skipTenantFilter = $config['skip_tenant_filter'] ?? false;

            // The prefixed collection name that exists in Typesense
            $prefixedCollectionName = $prefix.$collection;

            if ($isSuperAdmin) {
                // Super admins get unrestricted access but scoped to specific collection
                $scopedKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
                    'collection' => $prefixedCollectionName,
                    'expires_at' => $expiresAt,
                ]);

                $collections[$collection] = [
                    'key' => $scopedKey,
                    'tenant_ids' => [],
                    'institution_ids' => [],
                    'has_access' => true,
                    'scope' => 'all',
                ];
            } elseif ($skipTenantFilter && ! $permission) {
                // Collections that skip tenant filtering AND have no permission requirement
                // (e.g., documents are publicly accessible to all authenticated users)
                $scopedKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
                    'collection' => $prefixedCollectionName,
                    'expires_at' => $expiresAt,
                ]);

                $collections[$collection] = [
                    'key' => $scopedKey,
                    'tenant_ids' => [],
                    'institution_ids' => [],
                    'has_access' => true,
                    'scope' => 'public',
                ];
            } elseif ($skipTenantFilter && $permission && $this->authorizer->check($permission)) {
                // Collections that skip tenant filtering but require a base permission
                $scopedKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
                    'collection' => $prefixedCollectionName,
                    'expires_at' => $expiresAt,
                ]);

                $collections[$collection] = [
                    'key' => $scopedKey,
                    'tenant_ids' => [],
                    'institution_ids' => [],
                    'has_access' => true,
                    'scope' => 'public',
                ];
            } elseif ($permission && $this->authorizer->check($permission)) {
                // User has .padalinys permission - get their accessible tenants for this collection
                $tenantIds = $this->getTenantIdsForPermission($permission);

                // Also check for institution-based access that extends beyond tenant boundaries
                // (relationships, coordinator access, direct duties in other tenants)
                $isInstitutionsCollection = $collection === 'institutions';
                $institutionIds = $this->getInstitutionIdsForOwnPermission($ownPermission ?? $permission, $user, $isInstitutionsCollection);
                $directInstitutionIds = $this->institutionAccessService->getUserDutyInstitutionIds($user);

                // Build combined filter: tenant_ids OR institution_ids
                $filterBy = $this->buildCombinedFilterByClause($tenantIds, $institutionIds);

                if (! $filterBy) {
                    // No tenants and no institutions - skip this collection
                    continue;
                }

                $scopedKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
                    'collection' => $prefixedCollectionName,
                    'filter_by' => $filterBy,
                    'expires_at' => $expiresAt,
                ]);

                $collections[$collection] = [
                    'key' => $scopedKey,
                    'tenant_ids' => $tenantIds->toArray(),
                    'institution_ids' => $institutionIds->toArray(),
                    'direct_institution_ids' => $directInstitutionIds->toArray(),
                    'has_access' => true,
                    'scope' => 'combined',
                ];
            } elseif ($ownPermission && $this->authorizer->check($ownPermission)) {
                // User has .own permission - filter by their accessible institutions
                // Includes: direct duties, relationships, and coordinator access
                $isInstitutionsCollection = $collection === 'institutions';
                $institutionIds = $this->getInstitutionIdsForOwnPermission($ownPermission, $user, $isInstitutionsCollection);

                // If user has permission but no institutions (edge case), skip this collection
                if ($institutionIds->isEmpty()) {
                    continue;
                }

                // Get direct institution IDs for frontend to differentiate related results
                $directInstitutionIds = $this->institutionAccessService->getUserDutyInstitutionIds($user);

                $filterBy = $this->buildInstitutionFilterByClause($institutionIds);

                $scopedKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
                    'collection' => $prefixedCollectionName,
                    'filter_by' => $filterBy,
                    'expires_at' => $expiresAt,
                ]);

                $collections[$collection] = [
                    'key' => $scopedKey,
                    'tenant_ids' => [],
                    'institution_ids' => $institutionIds->toArray(),
                    'direct_institution_ids' => $directInstitutionIds->toArray(),
                    'has_access' => true,
                    'scope' => 'own',
                ];
            }
            // No access = collection is excluded entirely from response
        }

        // Generate an unrestricted header key for multi_search endpoint authentication
        // This key has no collection/filter restrictions - it's only for endpoint access
        // Individual search requests use their own collection-scoped keys
        $headerKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
            'expires_at' => $expiresAt,
        ]);

        return [
            'collections' => $collections,
            'header_key' => $headerKey,
            'expires_at' => $expiresAt,
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    /**
     * Get tenant IDs for a specific permission
     *
     * @return Collection<int, int>
     */
    protected function getTenantIdsForPermission(string $permission): Collection
    {
        if ($this->authorizer->check($permission)) {
            $tenants = $this->authorizer->getTenants($permission);

            return $tenants->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        }

        return collect();
    }

    /**
     * Get institution IDs for .own permission scope
     *
     * For .own permissions, access is based on:
     * 1. User's direct duty institutions
     * 2. Institutions accessible via authorized relationships (outgoing/sibling)
     *
     * This unified approach ensures search results match what users can see
     * in the dashboard and what policies allow them to view.
     *
     * For the institutions collection itself (self-referential), having the
     * institutions.read.own permission grants access to ALL institutions
     * the user can access through any of the above paths.
     *
     * @return Collection<int, string>
     */
    protected function getInstitutionIdsForOwnPermission(string $ownPermission, User $user, bool $isInstitutionsCollection = false): Collection
    {
        if ($this->authorizer->check($ownPermission)) {
            // Get accessible institutions (direct duties + related institutions)
            return $this->institutionAccessService->getAccessibleInstitutionIds(
                $user,
                includeRelated: true
            );
        }

        return collect();
    }

    /**
     * Get tenant IDs the user can access for admin search (legacy method)
     *
     * @deprecated Use getTenantIdsForPermission() with specific permission
     */
    protected function getUserAccessibleTenantIds(User $user): Collection
    {
        $this->authorizer->forUser($user);

        return $this->getTenantIdsForPermission('meetings.read.padalinys');
    }

    /**
     * Build filter_by clause for Typesense scoped key
     *
     * @param  Collection<int, int>  $tenantIds
     */
    protected function buildFilterByClause(Collection $tenantIds): string
    {
        if ($tenantIds->isEmpty()) {
            // No access - filter to impossible value to return no results
            return 'tenant_ids:=-1';
        }

        // Typesense array filtering: tenant_ids:[1,2,3] matches if any element matches
        $ids = $tenantIds->implode(',');

        return "tenant_ids:=[{$ids}]";
    }

    /**
     * Build filter_by clause for institution-based .own filtering
     *
     * Uses institution_ids field which must be indexed in the searchable array.
     * Matches documents where any of the user's institutions are involved.
     * Institution IDs are ULIDs (strings), so we use string array filtering.
     *
     * @param  Collection<int, string>  $institutionIds
     */
    protected function buildInstitutionFilterByClause(Collection $institutionIds): string
    {
        if ($institutionIds->isEmpty()) {
            // No access - filter to impossible value to return no results
            return 'institution_ids:=impossible_id';
        }

        // Typesense string array filtering: institution_ids:[`id1`, `id2`] matches if any element matches
        // Use backticks for string values in Typesense filters
        $ids = $institutionIds->map(fn ($id) => "`{$id}`")->implode(',');

        return "institution_ids:=[{$ids}]";
    }

    /**
     * Build combined filter_by clause for both tenant and institution access.
     *
     * This combines:
     * - Tenant-based access (from .padalinys permission)
     * - Institution-based access (from relationships, coordinator access, and direct duties)
     *
     * Uses OR logic: documents matching EITHER the tenant filter OR the institution filter are returned.
     *
     * @param  Collection<int, int>  $tenantIds
     * @param  Collection<int, string>  $institutionIds
     */
    protected function buildCombinedFilterByClause(Collection $tenantIds, Collection $institutionIds): ?string
    {
        $filters = [];

        if ($tenantIds->isNotEmpty()) {
            $ids = $tenantIds->implode(',');
            $filters[] = "tenant_ids:=[{$ids}]";
        }

        if ($institutionIds->isNotEmpty()) {
            $ids = $institutionIds->map(fn ($id) => "`{$id}`")->implode(',');
            $filters[] = "institution_ids:=[{$ids}]";
        }

        if (empty($filters)) {
            return null;
        }

        if (count($filters) === 1) {
            return $filters[0];
        }

        // Combine with OR - documents matching either filter are returned
        return '('.implode(' || ', $filters).')';
    }

    /**
     * Get the cache key for a user's scoped search keys
     */
    public static function getCacheKey(string $userId): string
    {
        return "typesense_scoped_keys:{$userId}";
    }

    /**
     * Invalidate the scoped key cache for a user
     */
    public static function invalidateForUser(string $userId): void
    {
        Cache::forget(self::getCacheKey($userId));
    }

    /**
     * Invalidate scoped keys for all users with a specific duty
     */
    public static function invalidateForDuty(\App\Models\Duty $duty): void
    {
        $duty->loadMissing('users');
        foreach ($duty->users as $user) {
            self::invalidateForUser($user->id);
        }
    }

    /**
     * Invalidate scoped keys for all users with a specific role
     */
    public static function invalidateForRole(\App\Models\Role $role): void
    {
        // Direct role assignments
        $role->loadMissing('users');
        foreach ($role->users as $user) {
            self::invalidateForUser($user->id);
        }

        // Role assignments through duties
        $role->loadMissing('duties.users');
        foreach ($role->duties as $duty) {
            foreach ($duty->users as $user) {
                self::invalidateForUser($user->id);
            }
        }
    }

    /**
     * Get the list of admin collections (with prefix)
     */
    public static function getAdminCollections(): array
    {
        return TypesenseCollectionConfig::getAdminCollectionNames();
    }

    /**
     * Check if Typesense is configured for scoped key generation
     */
    public static function isConfigured(): bool
    {
        $searchOnlyKey = Config::get('scout.typesense.client-settings.search_only_key');
        $apiKey = Config::get('scout.typesense.client-settings.api_key');

        return ! empty($searchOnlyKey) && ! empty($apiKey);
    }

    /**
     * Get the permission required for a specific collection
     */
    public static function getPermissionForCollection(string $collection): ?string
    {
        return TypesenseCollectionConfig::getPermissionForCollection($collection);
    }
}
