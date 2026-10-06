<?php

namespace App\Support\Experiments;

use App\Models\Tenant;
use App\Models\User;

final class GoalsExperiment
{
    /** @return list<int> */
    public static function enabledTenantIds(): array
    {
        $key = self::class.'.tenants';

        // Model instances and navigation caches may outlive the enrollment they loaded.
        if (! request()->attributes->has($key)) {
            request()->attributes->set($key, Tenant::query()->where('goals_enabled', true)->orderBy('id')->pluck('id')->all());
        }

        return request()->attributes->get($key);
    }

    public static function enabledForTenant(?Tenant $tenant): bool
    {
        return $tenant !== null && in_array($tenant->id, self::enabledTenantIds(), true);
    }

    public static function enabledForUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $key = self::class.'.user.'.$user->id;

        // Enrollment must be checked again on the next request.
        if (request()->attributes->has($key)) {
            return request()->attributes->get($key);
        }

        $enabled = $user->isSuperAdmin()
            ? self::enabledTenantIds() !== []
            : $user->current_duties()->whereHas('institution', fn ($query) => $query->whereIn('tenant_id', self::enabledTenantIds()))->exists();

        request()->attributes->set($key, $enabled);

        return $enabled;
    }
}
