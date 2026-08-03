<?php

namespace App\Models;

use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $title
 * @property string $image_url
 * @property string $link_url
 * @property string $lang
 * @property int $order
 * @property int $is_active
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Tenant $tenant
 *
 * @method static \Database\Factories\BannerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Banner extends Model
{
    use HasFactory, LogsModelActivity, Searchable, SoftDeletes;

    #[\Override]
    protected $guarded = [];

    #[\Override]
    protected static function booted()
    {
        static::creating(function (self $banner): void {
            $tenantId = data_get($banner, 'tenant_id');

            if ($tenantId === null) {
                return;
            }

            $order = data_get($banner, 'order');

            // Both queries include trashed banners: banners_order_padalinys_id_unique
            // covers (order, tenant_id) with no regard for deleted_at, so a trashed
            // banner still owns its slot and handing it out again is a duplicate key.
            if ($order === null || self::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('order', $order)
                ->exists()) {
                $maxOrder = self::withTrashed()
                    ->where('tenant_id', $tenantId)
                    ->max('order');

                $banner->order = ($maxOrder ?? 0) + 1;
            }
        });

        static::saved(function ($banner): void {
            Cache::tags(['banners', "tenant_{$banner->tenant_id}"])->flush();
        });

        static::deleted(function ($banner): void {
            Cache::tags(['banners', "tenant_{$banner->tenant_id}"])->flush();
        });
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenants()
    {
        return $this->tenant();
    }
}
