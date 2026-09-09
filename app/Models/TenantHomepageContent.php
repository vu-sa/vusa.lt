<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $content_id
 * @property string $locale
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Content $content
 * @property-read Tenant $tenant
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantHomepageContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantHomepageContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantHomepageContent query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['content_id', 'locale'])]
class TenantHomepageContent extends Model
{
    #[\Override]
    protected static function booted(): void
    {
        static::saved(function (self $homepageContent): void {
            $homepageContent->forgetCachedHomepage();
        });

        static::deleted(function (self $homepageContent): void {
            $homepageContent->forgetCachedHomepage();
        });
    }

    private function forgetCachedHomepage(): void
    {
        Cache::tags(['homepage', "tenant_{$this->tenant_id}", "locale_{$this->locale}"])
            ->forget("homepage_content_{$this->tenant_id}_{$this->locale}");
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
