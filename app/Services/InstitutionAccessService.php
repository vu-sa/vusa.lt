<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized service for determining which institutions a user can access.
 *
 * This service consolidates institution access logic that was previously scattered across:
 * - MeetingPolicy::hasAuthorizedRelationshipAccess()
 * - TypesenseScopedKeyService::getInstitutionIdsForOwnPermission()
 * - DashboardController::atstovavimas() related institutions logic
 *
 * Access Types:
 * 1. **Direct duty access**: Institutions where user has an active duty
 * 2. **Relationship access**: Institutions related via authorized relationships (outgoing/sibling)
 * 3. **Coordinator access**: Institutions in tenants where user has a coordinator role (from AtstovavimasSettings)
 *
 * Cache Strategy:
 * - All access computations are cached per-user with TTL of 1 hour
 * - Cache is invalidated via:
 *   - UserPermissionObserver (duty/role changes)
 *   - RelationshipableObserver (relationship changes) - triggers invalidation for affected institutions' users
 *
 * @see \App\Services\RelationshipService for institution relationship logic
 * @see \App\Settings\AtstovavimasSettings for coordinator role configuration
 */
class InstitutionAccessService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    public const CACHE_TTL = 3600;

    /**
     * Get all institution IDs that a user can access.
     *
     * This is the primary method for determining institution access and includes:
     * - User's direct duty institutions
     * - Institutions accessible via authorized relationships (if $includeRelated is true)
     * - Institutions in coordinator-visible tenants (if $includeCoordinatorAccess is true)
     *
     * @param  User  $user  The user to check access for
     * @param  bool  $includeRelated  Whether to include relationship-based access
     * @param  bool  $includeCoordinatorAccess  Whether to include coordinator tenant access
     * @return Collection<int, string> Collection of institution IDs (ULIDs)
     */
    public function getAccessibleInstitutionIds(
        User $user,
        bool $includeRelated = true,
        bool $includeCoordinatorAccess = true
    ): Collection {
        $cacheKey = self::getAccessCacheKey($user->id, $includeRelated, $includeCoordinatorAccess);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $includeRelated, $includeCoordinatorAccess) {
            $institutionIds = collect();

            // 1. Direct duty institutions
            $directInstitutionIds = $this->getUserDutyInstitutionIds($user);
            $institutionIds = $institutionIds->merge($directInstitutionIds);

            // 2. Relationship-based access
            if ($includeRelated && $directInstitutionIds->isNotEmpty()) {
                $relatedInstitutionIds = $this->getRelatedInstitutionIds($user, $directInstitutionIds);
                $institutionIds = $institutionIds->merge($relatedInstitutionIds);
            }

            // 3. Coordinator tenant access
            // TODO: Consider if this should use permissions (institutions.read.padalinys) instead of
            // AtstovavimasSettings roles. The current approach matches the dashboard behavior but
            // creates a parallel authorization path that could be confusing.
            if ($includeCoordinatorAccess) {
                $coordinatorInstitutionIds = $this->getCoordinatorAccessibleInstitutionIds($user);
                $institutionIds = $institutionIds->merge($coordinatorInstitutionIds);
            }

            return $institutionIds->unique()->values();
        });
    }

    /**
     * Get institution IDs where the user has a direct duty (current/active).
     *
     * @return Collection<int, string> Collection of institution IDs (ULIDs)
     */
    public function getUserDutyInstitutionIds(User $user): Collection
    {
        return $user->current_duties()
            ->whereNotNull('institution_id')
            ->pluck('institution_id')
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Get institution IDs from all user duties (including historical).
     * Used for the institutions collection where all user duties grant access.
     *
     * @return Collection<int, string> Collection of institution IDs (ULIDs)
     */
    public function getAllDutyInstitutionIds(User $user): Collection
    {
        return $user->duties()
            ->whereNotNull('institution_id')
            ->pluck('institution_id')
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Get institution IDs accessible via authorized relationships.
     *
     * For each of the user's duty institutions, finds related institutions
     * where the relationship is "authorized" (outgoing or sibling direction).
     *
     * @param  User  $user  The user
     * @param  Collection<int, string>|null  $dutyInstitutionIds  Pre-fetched duty institution IDs
     * @return Collection<int, string> Collection of related institution IDs (ULIDs)
     */
    public function getRelatedInstitutionIds(User $user, ?Collection $dutyInstitutionIds = null): Collection
    {
        $dutyInstitutionIds = $dutyInstitutionIds ?? $this->getUserDutyInstitutionIds($user);

        if ($dutyInstitutionIds->isEmpty()) {
            return collect();
        }

        $relatedIds = collect();

        // Load user's institutions
        $institutions = Institution::whereIn('id', $dutyInstitutionIds)->get();

        foreach ($institutions as $institution) {
            // Get related institutions with authorized access only
            $related = RelationshipService::getRelatedInstitutions($institution, authorizedOnly: true);
            $relatedIds = $relatedIds->merge($related->pluck('id'));
        }

        // Remove the user's own institutions from related (they're already included in direct access)
        return $relatedIds
            ->diff($dutyInstitutionIds)
            ->unique()
            ->values();
    }

    /**
     * Get institution IDs from coordinator-visible tenants.
     *
     * Users with coordinator roles (defined in AtstovavimasSettings) can see
     * all institutions in their coordinator tenants. This matches the dashboard behavior.
     *
     * TODO: Consider unifying this with institutions.read.padalinys permission instead
     * of using a separate settings-based role configuration.
     *
     * @return Collection<int, string> Collection of institution IDs (ULIDs)
     */
    protected function getCoordinatorAccessibleInstitutionIds(User $user): Collection
    {
        $settings = app(AtstovavimasSettings::class);

        // Get tenants where user has coordinator visibility
        $visibleTenantIds = $settings->getVisibleTenantIds($user);

        if ($visibleTenantIds->isEmpty()) {
            return collect();
        }

        // Get all institutions in those tenants
        return Institution::whereIn('tenant_id', $visibleTenantIds)
            ->pluck('id');
    }

    /**
     * Check if a user can access a specific institution.
     *
     * @param  User  $user  The user
     * @param  Institution|string  $institution  The institution or institution ID
     */
    public function canAccessInstitution(User $user, Institution|string $institution): bool
    {
        $institutionId = $institution instanceof Institution ? $institution->id : $institution;
        $accessibleIds = $this->getAccessibleInstitutionIds($user);

        return $accessibleIds->contains($institutionId);
    }

    /**
     * Check if a user can access a meeting via their institution relationships.
     *
     * This replaces MeetingPolicy::hasAuthorizedRelationshipAccess() with a
     * centralized implementation.
     *
     * @param  User  $user  The user
     * @param  Collection<int, string>|array  $meetingInstitutionIds  Institution IDs linked to the meeting
     */
    public function canAccessMeetingViaRelationships(User $user, Collection|array $meetingInstitutionIds): bool
    {
        if (empty($meetingInstitutionIds)) {
            return false;
        }

        $meetingInstitutionIds = $meetingInstitutionIds instanceof Collection
            ? $meetingInstitutionIds
            : collect($meetingInstitutionIds);

        $accessibleIds = $this->getAccessibleInstitutionIds($user);

        return $accessibleIds->intersect($meetingInstitutionIds)->isNotEmpty();
    }

    /**
     * Get the cache key for a user's accessible institutions.
     */
    public static function getAccessCacheKey(string $userId, bool $includeRelated = true, bool $includeCoordinator = true): string
    {
        $suffix = ($includeRelated ? 'r' : '').($includeCoordinator ? 'c' : '');

        return "institution_access:{$userId}:{$suffix}";
    }

    /**
     * Invalidate all access caches for a user.
     */
    public static function invalidateForUser(string $userId): void
    {
        // Invalidate all variants of the cache
        $variants = ['rc', 'r', 'c', ''];
        foreach ($variants as $variant) {
            Cache::forget("institution_access:{$userId}:{$variant}");
        }
    }

    /**
     * Invalidate access caches for all users with duties in a specific institution.
     * Called when institution relationships change.
     */
    public static function invalidateForInstitution(string $institutionId): void
    {
        $institution = Institution::with('duties.users')->find($institutionId);

        if (! $institution) {
            return;
        }

        foreach ($institution->duties as $duty) {
            foreach ($duty->users as $user) {
                self::invalidateForUser($user->id);
            }
        }
    }

    /**
     * Invalidate access caches for all users who might have access to institutions
     * affected by a relationship change.
     */
    public static function invalidateForRelationshipChange(string $sourceInstitutionId, string $targetInstitutionId): void
    {
        self::invalidateForInstitution($sourceInstitutionId);
        self::invalidateForInstitution($targetInstitutionId);
    }
}
