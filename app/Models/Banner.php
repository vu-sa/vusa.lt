<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Tenant $tenant
 *
 * @method static \Database\Factories\BannerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner query()
 *
 * @mixin \Eloquent
 */
class Banner extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function (self $banner): void {
            $tenantId = data_get($banner, 'tenant_id');

            if ($tenantId === null) {
                return;
            }

            $order = data_get($banner, 'order');

            if ($order === null || self::query()
                ->where('tenant_id', $tenantId)
                ->where('order', $order)
                ->exists()) {
                $maxOrder = self::query()
                    ->where('tenant_id', $tenantId)
                    ->max('order');

                $banner->order = ($maxOrder ?? 0) + 1;
            }
        });

        static::saved(function ($banner) {
            Cache::tags(['banners', "tenant_{$banner->tenant_id}"])->flush();
        });

        static::deleted(function ($banner) {
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
