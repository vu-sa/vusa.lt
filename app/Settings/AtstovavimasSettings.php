<?php

namespace App\Settings;

use App\Enums\TenantType;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Policies\Traits\HasCommonChecks;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelSettings\Settings;

/**
 * Settings for the Atstovavimas (Representation) dashboard feature.
 *
 * This class handles:
 * - **Visibility**: Delegates to ModelAuthorizer using permissions (institutions.read.padalinys, institutions.read.*)
 *
 * The institution visibility is now unified with the permission system:
 * - `institutions.read.*` → can see all institutions
 * - `institutions.read.padalinys` → can see institutions in authorized tenants
 * - `institutions.read.own` → can see institutions where user has duties + related institutions
 *
 * Coordinators are not a setting: they hold the studentų atstovų koordinavimas duty
 * responsibility (App\Services\ResponsibilityResolver).
 *
 * @see ModelAuthorizer for permission-based authorization
 * @see HasCommonChecks for policy authorization patterns
 */
class AtstovavimasSettings extends Settings
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * The type ID that identifies the root student representative organ type.
     * When null, defaults to the type with slug 'studentu-atstovu-organas'.
     */
    public ?int $student_rep_root_type_id = null;

    public static function group(): string
    {
        return 'atstovavimas';
    }

    /**
     * Get visible tenant IDs for a user based on permissions.
     *
     * This uses the permission system via ModelAuthorizer:
     * - Super admins or users with institutions.read.* → all tenants
     * - Users with institutions.read.padalinys → their authorized tenants
     * - Others → empty (they only see their own institutions)
     */
    public function getVisibleTenantIds(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Tenant::query()->representational()->pluck('id');
        }

        $authorizer = app(ModelAuthorizer::class);

        // Check for global read permission
        if ($authorizer->allows($user, 'institutions.read.*')) {
            return Tenant::query()->representational()->pluck('id');
        }

        // Check for padalinys-level permission
        $tenants = $authorizer->tenants($user, 'institutions.read.padalinys');

        if ($tenants->isNotEmpty()) {
            return $tenants
                ->whereIn('type', TenantType::representational())
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();
        }

        return collect();
    }

    /**
     * Get tenant visibility role IDs.
     * These are roles that grant visibility to institutions within specific tenants.
     *
     * Currently returns empty collection - can be configured via settings if needed.
     */
    public function getTenantVisibilityRoleIds(): Collection
    {
        // Currently not configured - the institution manager role handles tenant visibility
        // This could be extended to include additional visibility roles if needed
        return collect();
    }

    /**
     * Get global visibility role IDs.
     * These are roles that grant visibility to all institutions globally.
     *
     * Currently returns empty collection - super admin check handles this case.
     */
    public function getGlobalVisibilityRoleIds(): Collection
    {
        // Currently not configured - super admin role handles global visibility
        // This could be extended to include additional global visibility roles if needed
        return collect();
    }

    /**
     * Get the student representative root type model.
     * Defaults to the type with slug 'studentu-atstovu-organas'.
     */
    public function getStudentRepRootType(): ?Type
    {
        if ($this->student_rep_root_type_id) {
            $type = Type::find($this->student_rep_root_type_id);
            if ($type) {
                return $type;
            }
        }

        return Type::query()->where('slug', 'studentu-atstovu-organas')->first();
    }

    /**
     * Get all type IDs for student representative institutions (root type + all descendants).
     *
     * @return Collection<int, int>
     */
    public function getStudentRepInstitutionTypeIds(): Collection
    {
        return Cache::remember('atstovavimas:student_rep_type_ids', self::CACHE_TTL, function () {
            $rootType = $this->getStudentRepRootType();

            if (! $rootType) {
                return collect();
            }

            return $rootType->getDescendantsAndSelf()->pluck('id');
        });
    }

    /**
     * Get all type slugs for student representative institutions (root type + all descendants).
     *
     * @return Collection<int, string>
     */
    public function getStudentRepInstitutionTypeSlugs(): Collection
    {
        return Cache::remember('atstovavimas:student_rep_type_slugs', self::CACHE_TTL, function () {
            $rootType = $this->getStudentRepRootType();

            if (! $rootType) {
                return collect(['studentu-atstovu-organas']);
            }

            return $rootType->getDescendantsAndSelf()->pluck('slug')->filter()->values();
        });
    }

    /**
     * Check if an institution is a student representative organ (has root type or any descendant).
     */
    public function isStudentRepresentativeInstitution(Institution $institution): bool
    {
        $repTypeIds = $this->getStudentRepInstitutionTypeIds();

        if ($repTypeIds->isEmpty()) {
            return false;
        }

        if (! $institution->relationLoaded('types')) {
            $institution->load('types');
        }

        return $institution->types->pluck('id')->intersect($repTypeIds)->isNotEmpty();
    }

    /**
     * Clear the cached student rep type IDs and slugs.
     */
    public static function clearStudentRepTypeCache(): void
    {
        Cache::forget('atstovavimas:student_rep_type_ids');
        Cache::forget('atstovavimas:student_rep_type_slugs');
    }
}
