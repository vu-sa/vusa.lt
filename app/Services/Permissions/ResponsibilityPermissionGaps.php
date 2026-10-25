<?php

namespace App\Services\Permissions;

use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Role;
use Illuminate\Support\Str;

/**
 * Which of a purpose's minimum permissions a duty lacks, counting every responsibility it holds.
 * Reported only: checking a purpose never adds permissions.
 */
class ResponsibilityPermissionGaps
{
    /** A wider scope covers a narrower one. */
    private const array COVERED_BY = [
        'own' => ['own', 'padalinys', '*'],
        'padalinys' => ['padalinys', '*'],
        '*' => ['*'],
    ];

    /**
     * @return list<string>
     */
    public function missingFor(Duty $duty, ResponsibilityPurpose $purpose): array
    {
        $held = $this->heldBy($duty);

        return array_values(array_filter(
            $purpose->minimumPermissions(),
            fn (string $needed): bool => ! $this->covers($held, $needed),
        ));
    }

    /**
     * Duties holding the purpose-carrying responsibility that lack part of its minimum.
     *
     * @return list<array{duty: Duty, missing: list<string>}>
     */
    public function forRole(Role $role): array
    {
        if ($role->purpose === null || $role->purpose->minimumPermissions() === []) {
            return [];
        }

        return $role->duties()
            ->with(['roles.permissions', 'permissions', 'institution:id,name,tenant_id'])
            ->get()
            ->map(fn (Duty $duty): array => ['duty' => $duty, 'missing' => $this->missingFor($duty, $role->purpose)])
            ->filter(fn (array $gap): bool => $gap['missing'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $held
     */
    private function covers(array $held, string $needed): bool
    {
        $prefix = Str::beforeLast($needed, '.');
        $scope = Str::afterLast($needed, '.');

        foreach (self::COVERED_BY[$scope] ?? [$scope] as $wider) {
            if (in_array("{$prefix}.{$wider}", $held, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function heldBy(Duty $duty): array
    {
        $duty->loadMissing(['roles.permissions', 'permissions']);

        return $duty->roles->flatMap->permissions->concat($duty->permissions)->pluck('name')->unique()->values()->all();
    }
}
