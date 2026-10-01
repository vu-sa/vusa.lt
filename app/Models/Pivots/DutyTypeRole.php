<?php

namespace App\Models\Pivots;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\DutyType;
use App\Models\Role;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $role_id
 * @property int $duty_type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role $role
 * @property-read DutyType|null $type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyTypeRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyTypeRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyTypeRole query()
 *
 * @mixin \Eloquent
 */
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
