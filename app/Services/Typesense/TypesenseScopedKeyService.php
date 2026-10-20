<?php

namespace App\Services\Typesense;

use App\Models\Duty;
use App\Models\Pivots\AgendaItem;
use App\Models\Role;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\InstitutionRelationService;
use App\Services\MeetingAccessService;
use App\Services\ModelAuthorizer;
use App\Settings\MeetingSettings;
use App\Support\AuthorityCacheExpiry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Typesense\Client;

/** Scoped keys enforce visibility even when a browser supplies its own search filters. */
class TypesenseScopedKeyService
{
    public const KEY_EXPIRY = 3600;

    /** Refresh the cache before the credential itself expires. */
    protected const CACHE_TTL = 3500;

    public function __construct(protected Client $client, protected ModelAuthorizer $authorizer, protected InstitutionAccessService $institutionAccessService) {}

    /**
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

    /** Changes whenever the settings that decide public rows, or the relationship graph, do. */
    public static function visibilityVersion(): string
    {
        return md5(implode(':', [
            'meeting-baseline-v2',
            config('scout.typesense.client-settings.admin_search_key'),
            Cache::get('agenda-privacy-version', ''),
            InstitutionRelationService::version(),
            app(MeetingSettings::class)->getPublicMeetingInstitutionTypeIds()->sort()->implode(','),
        ]));
    }

    /**
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
            $skipTenantFilter = $config['skip_tenant_filter'] ?? false;
            $publicRows = $config['public_rows'] ?? null;

            // The prefixed collection name that exists in Typesense
            $prefixedCollectionName = $prefix.$collection;

            if ($isSuperAdmin) {
                $collections[$collection] = $this->unrestrictedCollection($parentKey, $prefixedCollectionName, $expiresAt, 'all');
            } elseif ($publicRows !== null) {
                $collections[$collection] = $this->publicCollection($user, $collection, $config, $publicRows, $parentKey, $expiresAt);
            } elseif ($skipTenantFilter) {
                if ($permission !== null && ! $this->authorizer->allows($user, $permission)) {
                    continue;
                }

                $collections[$collection] = $this->unrestrictedCollection($parentKey, $prefixedCollectionName, $expiresAt, 'public');
            } elseif ($permission) {
                if (! $this->authorizer->allows($user, $permission)) {
                    continue;
                }

                $tenantIds = $this->getTenantIdsForPermission($user, $permission);

                $filterBy = $this->buildCombinedFilterByClause($tenantIds, collect());

                if (! $filterBy) {
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
                    'institution_ids' => [],
                    'direct_institution_ids' => [],
                    'has_access' => true,
                    'scope' => 'combined',
                ];
            }
        }

        // Without a per-search key, the transport credential must never return records.
        $headerKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
            'filter_by' => 'id:=__transport_only__',
            'expires_at' => $expiresAt,
        ]);

        return [
            'collections' => $collections,
            'header_key' => $headerKey,
            'expires_at' => $expiresAt,
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    /** @return array{key: string, tenant_ids: array, institution_ids: array, has_access: bool, scope: string} */
    private function unrestrictedCollection(string $parentKey, string $collection, int $expiresAt, string $scope): array
    {
        return [
            'key' => $this->client->getKeys()->generateScopedSearchKey($parentKey, [
                'collection' => $collection,
                'expires_at' => $expiresAt,
            ]),
            'tenant_ids' => [],
            'institution_ids' => [],
            'has_access' => true,
            'scope' => $scope,
        ];
    }

    /**
     * @param  array{permission: string|null, baseline_own?: true}  $config
     * @param  'meeting_types'|'active'  $publicRows
     * @return array{key: string, tenant_ids: array, institution_ids: array, direct_institution_ids: array, has_access: bool, scope: string}
     */
    private function publicCollection(User $user, string $collection, array $config, string $publicRows, string $parentKey, int $expiresAt): array
    {
        $permission = $config['permission'];
        $prefixedCollectionName = config('scout.prefix', '').$collection;
        // Public rows for everyone; a permission the user lacks resolves to no ids and
        // simply drops its clause.
        $tenantIds = $permission ? $this->getTenantIdsForPermission($user, $permission) : collect();
        $institutionIds = ($config['baseline_own'] ?? false)
            ? $this->institutionAccessService->getAccessibleInstitutionIds($user)
            : collect();

        $filter = $this->buildCombinedFilterByClause($tenantIds, $institutionIds, $this->publicRowsClause($publicRows));
        if (in_array($collection, ['meetings', 'agenda_items'], true)) {
            $filter = app(MeetingAccessService::class)->searchFilter(
                $user,
                $collection === 'meetings' ? 'meetings' : 'agendaItems',
                $this->publicRowsClause($publicRows),
            );
        }
        if ($collection === 'agenda_items') {
            $privateIds = AgendaItem::query()->where('is_private', true)
                ->with(['meeting.institutions.types', 'meeting.institutions.tenant', 'tenants'])
                ->get()->filter(fn (AgendaItem $item): bool => Gate::forUser($user)->allows('view', $item))
                ->pluck('id')->map(fn ($id): string => '`'.$id.'`')->implode(',');
            $clauses = $filter ? ['('.$filter.') && is_private:=false'] : [];
            if ($privateIds !== '') {
                $clauses[] = 'is_private:=true && id:=['.$privateIds.']';
            }
            $filter = $clauses ? '('.implode(' || ', $clauses).')' : 'id:=__no_access__';
        }

        $scopedKey = $this->client->getKeys()->generateScopedSearchKey($parentKey, [
            'collection' => $prefixedCollectionName,
            'filter_by' => $filter
                // Nothing public and no permissions: an empty list, not a missing collection.
                ?? 'tenant_ids:=-1',
            'expires_at' => $expiresAt,
        ]);

        return [
            'key' => $scopedKey,
            'tenant_ids' => $tenantIds->toArray(),
            'institution_ids' => $institutionIds->toArray(),
            'direct_institution_ids' => $this->institutionAccessService->getUserDutyInstitutionIds($user)->toArray(),
            'has_access' => true,
            'scope' => 'combined',
        ];
    }

    /** @return Collection<int, int> */
    protected function getTenantIdsForPermission(User $user, string $permission): Collection
    {
        return $this->authorizer->tenants($user, $permission)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Independent access paths are alternatives; AND would hide baseline records.
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

    public static function getCacheKey(string $userId): string
    {
        return "typesense_scoped_keys:{$userId}";
    }

    public static function invalidateForUser(string $userId): void
    {
        Cache::forget(self::getCacheKey($userId));
    }

    public static function invalidateForDuty(Duty $duty): void
    {
        $duty->loadMissing('users');
        foreach ($duty->users as $user) {
            self::invalidateForUser($user->id);
        }
    }

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

    public static function getAdminCollections(): array
    {
        return TypesenseCollectionConfig::getAdminCollectionNames();
    }

    public static function isConfigured(): bool
    {
        $searchOnlyKey = Config::get('scout.typesense.client-settings.search_only_key');
        $apiKey = Config::get('scout.typesense.client-settings.api_key');

        return ! empty($searchOnlyKey) && ! empty($apiKey);
    }

    public static function getPermissionForCollection(string $collection): ?string
    {
        return TypesenseCollectionConfig::getPermissionForCollection($collection);
    }
}
