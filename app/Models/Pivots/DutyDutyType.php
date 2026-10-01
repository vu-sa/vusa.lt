<?php

namespace App\Models\Pivots;

use App\Models\Duty;
use App\Models\DutyType;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\PermissionMapBuilder;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[WithoutTimestamps]
class DutyDutyType extends Pivot
{
    protected static function booted(): void
    {
        static::created(function (self $assignment): void {
            $assignment->duty->roles()->syncWithoutDetaching($assignment->type->roles->modelKeys());
            $assignment->invalidateAccess();
        });

        static::deleted(function (self $assignment): void {
            $assignment->duty->roles()->detach($assignment->type->roles->modelKeys());
            $assignment->invalidateAccess();
        });
    }

    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class)->withTrashed();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DutyType::class, 'duty_type_id')->withTrashed();
    }

    private function invalidateAccess(): void
    {
        $this->duty->current_users->each(function ($user): void {
            app(ModelAuthorizer::class)->resetCache($user);
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
        });
    }
}
