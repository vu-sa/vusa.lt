<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string|null $link
 * @property string|null $text
 * @property string|null $icon
 * @property int|null $order
 * @property bool $is_active
 * @property bool $is_important
 * @property int $tenant_id
 * @property string|null $lang
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant $tenant
 *
 * @method static \Database\Factories\QuickLinkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuickLink withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QuickLink extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    #[\Override]
    protected $guarded = [];

    #[\Override]
    protected $casts = [
        'is_active' => 'boolean',
        'is_important' => 'boolean',
    ];

    #[\Override]
    protected static function booted()
    {
        static::saved(function ($quickLink): void {
            Cache::tags(['quick_links', "tenant_{$quickLink->tenant_id}", "locale_{$quickLink->lang}"])->flush();
        });

        static::deleted(function ($quickLink): void {
            Cache::tags(['quick_links', "tenant_{$quickLink->tenant_id}", "locale_{$quickLink->lang}"])->flush();
        });
    }

    public function toSearchableArray(): array
    {
        return [
            'text' => $this->text,
            'link' => $this->link,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
