<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string|null $link
 * @property string|null $text
 * @property string|null $icon
 * @property int|null $order
 * @property int $is_active
 * @property int $is_important
 * @property int $tenant_id
 * @property string|null $lang
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Tenant $tenant
 *
 * @method static \Database\Factories\QuickLinkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink query()
 *
 * @mixin \Eloquent
 */
class QuickLink extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(function ($quickLink) {
            Cache::tags(['quick_links', "tenant_{$quickLink->tenant_id}", "locale_{$quickLink->lang}"])->flush();
        });

        static::deleted(function ($quickLink) {
            Cache::tags(['quick_links', "tenant_{$quickLink->tenant_id}", "locale_{$quickLink->lang}"])->flush();
        });
    }

    public function toSearchableArray()
    {
        return [
            'text' => $this->text,
            'link' => $this->link,
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
