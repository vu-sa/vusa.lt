<?php

namespace App\Services\Permissions;

use App\Enums\ModelEnum;
use App\Models\User;

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
     * Model labels that do not map to a manageable admin resource and must be
     * excluded from the permission maps.
     *
     * @var list<string>
     */
    private const EXCLUDED_LABELS = ['reservationResource', 'file'];

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
        $labels = ModelEnum::toLabels();

        foreach (self::EXCLUDED_LABELS as $excluded) {
            $key = array_search($excluded, $labels);

            if ($key !== false) {
                unset($labels[$key]);
            }
        }

        return array_values($labels);
    }
}
