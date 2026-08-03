<?php

namespace App\Services\Permissions;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Builds the per-model authorization maps (viewAny / create) used to populate
 * the admin sidebar and to measure access changes.
 *
 * These methods are intentionally cache-free: HandleInertiaRequests wraps them
 * in Cache::remember for the shared Inertia props, while AccessChangeAnalyzer
 * needs fresh values when comparing capabilities before and after a change.
 */
class PermissionMapBuilder
{
    /**
     * Cache keys holding the per-user permission maps shared with Inertia.
     *
     * @var list<string>
     */
    private const array CACHE_KEY_PREFIXES = [
        self::INDEX_CACHE_PREFIX,
        self::CREATE_CACHE_PREFIX,
        self::FORCE_DELETE_CACHE_PREFIX,
    ];

    public const INDEX_CACHE_PREFIX = 'index-permissions-';

    public const CREATE_CACHE_PREFIX = 'create-permissions-';

    public const FORCE_DELETE_CACHE_PREFIX = 'force-delete-permissions-';

    /**
     * Invalidate every cached permission map for a user.
     *
     * Kept in one place so adding a new map cannot leave stale entries behind at
     * one of the several call sites that react to permission changes.
     */
    public static function forgetCachedMaps(int|string $userId): void
    {
        foreach (self::CACHE_KEY_PREFIXES as $prefix) {
            Cache::forget($prefix.$userId);
        }
    }

    /**
     * Model labels that do not map to a manageable admin resource and must be
     * excluded from the permission maps.
     *
     * @var list<string>
     */
    private const array EXCLUDED_LABELS = ['reservationResource', 'file'];

    /**
     * Build the viewAny permission map keyed by model label.
     *
     * @return array<string, bool>
     */
    public function indexMap(User $user): array
    {
        return $this->buildMap($user, 'viewAny');
    }

    /**
     * Build the create permission map keyed by model label.
     *
     * @return array<string, bool>
     */
    public function createMap(User $user): array
    {
        return $this->buildMap($user, 'create');
    }

    /**
     * Build the permanent-deletion capability map keyed by model label.
     *
     * This is a coarse, class-level hint used to decide whether the admin tables
     * should offer a "delete permanently" action at all. Unlike viewAny/create it
     * cannot go through the policy, because forceDelete authorizes a concrete
     * record; the per-record decision still happens server-side in ModelPolicy.
     *
     * Only soft-deletable models can be permanently deleted, so everything else
     * is reported as false.
     *
     * @return array<string, bool>
     */
    public function forceDeleteMap(User $user): array
    {
        $authorizer = app(ModelAuthorizer::class)->forUser($user);

        return collect($this->manageableLabels())
            ->mapWithKeys(function (string $model) use ($authorizer) {
                $resource = Str::plural($model);

                if (! ModelEnum::isSoftDeletable($resource)) {
                    return [$model => false];
                }

                $allowed = collect([
                    PermissionScopeEnum::PADALINYS->label(),
                    PermissionScopeEnum::ALL->label(),
                ])->contains(fn (string $scope) => $authorizer->check($resource.'.'.CRUDEnum::FORCE_DELETE->label().'.'.$scope));

                return [$model => $allowed];
            })
            ->toArray();
    }

    /**
     * @return array<string, bool>
     */
    private function buildMap(User $user, string $ability): array
    {
        return collect($this->manageableLabels())
            ->mapWithKeys(fn (string $model) => [
                $model => $user->can($ability, ['App\\Models\\'.ucfirst($model)]),
            ])
            ->toArray();
    }

    /**
     * @return list<string>
     */
    private function manageableLabels(): array
    {
        $labels = ModelEnum::labels();

        foreach (self::EXCLUDED_LABELS as $excluded) {
            $key = array_search($excluded, $labels);

            if ($key !== false) {
                unset($labels[$key]);
            }
        }

        return array_values($labels);
    }
}
