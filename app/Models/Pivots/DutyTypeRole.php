<?php

namespace App\Models\Pivots;

use App\Models\DutyType;
use App\Models\Role;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\PermissionMapBuilder;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DutyTypeRole extends Pivot
{
    protected static function booted(): void
    {
        static::created(function (self $grant): void {
            foreach ($grant->type->duties as $duty) {
                $duty->roles()->syncWithoutDetaching([$grant->role_id]);
                $duty->current_users->each(function ($user): void {
            app(ModelAuthorizer::class)->resetCache($user);
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
        });
            }
        });

        static::deleted(function (self $grant): void {
            foreach ($grant->type->duties as $duty) {
                $duty->roles()->detach([$grant->role_id]);
                $duty->current_users->each(function ($user): void {
            app(ModelAuthorizer::class)->resetCache($user);
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
        });
            }
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DutyType::class, 'duty_type_id')->withTrashed();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
