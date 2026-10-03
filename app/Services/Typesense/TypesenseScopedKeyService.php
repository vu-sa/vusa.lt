<?php

namespace App\Services\Typesense;

use App\Models\Duty;
use App\Models\Role;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
use App\Settings\MeetingSettings;
use App\Support\AuthorityCacheExpiry;
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

    public function __construct(protected Client $client, protected ModelAuthorizer $authorizer, protected InstitutionAccessService $institutionAccessService) {}

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
     * @return array{collections: array<string, array{key: string, tenant_ids: array<int>, institution_ids?: array<int>, direct_institution_ids?: array<int>, scope?: string, has_access: bool}>, expires_at: int, is_super_admin: bool, header_key?: string, visibility_version: string}
     */
    public function generateScopedKeysForUser(User $user): array
    {
        $cacheKey = self::getCacheKey($user->id);
        $cached = Cache::get($cacheKey);

        // Keys embed the public meeting types, so a settings change retires every cached key.
        if (is_array($cached) && ($cached['visibility_version'] ?? null) === self::visibilityVersion()) {
            return $cached;
        }

        $keys = [...$this->buildScopedKeys($user), 'visibility_version' => self::visibilityVersion()];
        // Refreshed a little before the key itself expires, so the browser never holds a dead key.
        Cache::put($cacheKey, $keys, max(1, $keys['expires_at'] - time() - (self::KEY_EXPIRY - self::CACHE_TTL)));

        return $keys;
    }

    /** Changes whenever the settings that decide public rows do. */
    public static function visibilityVersion(): string
    {
        return md5(app(MeetingSettings::class)->getPublicMeetingInstitutionTypeIds()->sort()->implode(','));
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
     * @return array{collections: array<string, array{key: string, tenant_ids: array<int>, institution_ids?: array<int>, direct_institution_ids?: array<int>, scope?: string, has_access: bool}>, expires_at: int, is_super_admin: bool, header_key?: string}
     */
    protected function buildScopedKeys(User $user): array
    {
        $parentKey = Config::get('scout.typesense.client-settings.admin_search_key')
            ?? Config::get('scout.typesense.client-settings.search_only_key');
        // A key also dies when one of the user's duties ends: its filters carry their access.
        $expiresAt = AuthorityCacheExpiry::for($user, self::KEY_EXPIRY)->timestamp;
        $prefix = config('scout.prefix', '');

        $isSuperAdmin = $user->isSuperAdmin();

        $collections = [];
        $adminCollections = TypesenseCollectionConfig::getAdminCollections();

        foreach ($adminCollections as $collection => $config) {
            $permission = $config['permission'] ?? null;
            $ownPermission = $config['own_permission'] ?? null;
            $skipTenantFilter = $config['skip_tenant_filter'] ?? false;
            $publicRows = $config['public_rows'] ?? null;

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
            } elseif ($publicRows !== null) {
                // Public rows for everyone; a permission the user lacks resolves to no ids and
                // simply drops its clause.
                $tenantIds = $permission ? $this->getTenantIdsForPermission($user, $permission) : collect();
                $institutionIds = $ownPermission ? $this->getInstitutionIdsForOwnPermission($ownPermission, $user) : collect();

                $scopedKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
                    'collection' => $prefixedCollectionName,
                    'filter_by' => $this->buildCombinedFilterByClause($tenantIds, $institutionIds, $this->publicRowsClause($publicRows))
                        // Nothing public and no permissions: an empty list, not a missing collection.
                        ?? 'tenant_ids:=-1',
                    'expires_at' => $expiresAt,
                ]);

                $collections[$collection] = [
                    'key' => $scopedKey,
                    'tenant_ids' => $tenantIds->toArray(),
                    'institution_ids' => $institutionIds->toArray(),
                    'direct_institution_ids' => $this->institutionAccessService->getUserDutyInstitutionIds($user)->toArray(),
                    'has_access' => true,
                    'scope' => 'combined',
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
            } elseif ($skipTenantFilter) {
                if (! $this->authorizer->allows($user, $permission)) {
                    continue;
                }

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
            } elseif ($permission) {
                if (! $this->authorizer->allows($user, $permission)) {
                    continue;
                }

                // User has .padalinys permission - get their accessible tenants for this collection
                $tenantIds = $this->getTenantIdsForPermission($user, $permission);

                // Include institution-based access only when .own permission is defined for this collection
                $institutionIds = collect();
                $directInstitutionIds = collect();

                if ($ownPermission) {
                    // Also check for institution-based access that extends beyond tenant boundaries
                    // (relationships, coordinator access, direct duties in other tenants)
                    $isInstitutionsCollection = $collection === 'institutions';
                    $institutionIds = $this->getInstitutionIdsForOwnPermission($ownPermission, $user, $isInstitutionsCollection);
                    $directInstitutionIds = $this->institutionAccessService->getUserDutyInstitutionIds($user);
                }

                // Build combined filter: tenant_ids OR institution_ids (if present)
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
            } elseif ($ownPermission && $this->authorizer->allows($user, $ownPermission)) {
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
    protected function getTenantIdsForPermission(User $user, string $permission): Collection
    {
        return $this->authorizer->tenants($user, $permission)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
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
        if ($this->authorizer->allows($user, $ownPermission)) {
            // Get accessible institutions (direct duties + related institutions)
            return $this->institutionAccessService->getAccessibleInstitutionIds(
                $user,
                includeRelated: true
            );
        }

        return collect();
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
     * `$publicClause` adds the rows anyone may read to the alternatives.
     *
     * @param  Collection<int, int>  $tenantIds
     * @param  Collection<int, string>  $institutionIds
     */
    protected function buildCombinedFilterByClause(Collection $tenantIds, Collection $institutionIds, ?string $publicClause = null): ?string
    {
        $filters = $publicClause !== null ? [$publicClause] : [];

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
     * The rows anyone may read, from the indexed facts and the current settings — the same
     * inputs Meeting::isPubliclyVisible() and InstitutionPolicy::viewSummary() use.
     *
     * @param  'meeting_types'|'active'  $publicRows
     */
    protected function publicRowsClause(string $publicRows): ?string
    {
        if ($publicRows === 'active') {
            return 'is_active:=true';
        }

        $typeIds = app(MeetingSettings::class)->getPublicMeetingInstitutionTypeIds();

        return $typeIds->isEmpty() ? null : 'institution_type_ids:=['.$typeIds->implode(',').']';
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
    public static function invalidateForDuty(Duty $duty): void
    {
        $duty->loadMissing('users');
        foreach ($duty->users as $user) {
            self::invalidateForUser($user->id);
        }
    }

    /**
     * Invalidate scoped keys for all users with a specific role
     */
    public static function invalidateForRole(Role $role): void
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
