<?php

namespace App\Models;

use App\Enums\TenantType;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property TenantType|null $type
 * @property string $fullname
 * @property string $shortname
 * @property string $alias
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $shortname_vu
 * @property string|null $primary_institution_id
 * @property int|null $content_id
 * @property-read Collection<int, Banner> $banners
 * @property-read Collection<int, Calendar> $calendar
 * @property-read Content|null $content
 * @property-read Collection<int, Duty> $duties
 * @property-read Collection<int, Institution> $institutions
 * @property-read Collection<int, News> $news
 * @property-read Collection<int, Page> $pages
 * @property-read Institution|null $primary_institution
 * @property-read Collection<int, QuickLink> $quickLinks
 * @property-read Collection<int, Reservation> $reservations
 * @property-read Collection<int, \App\Models\Resource> $resources
 * @property-read Collection<int, StudySet> $studySets
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read int|null $reservations_count
 *
 * @method static \Database\Factories\TenantFactory factory($count = null, $state = [])
 * @method static Builder<static>|Tenant newModelQuery()
 * @method static Builder<static>|Tenant newQuery()
 * @method static Builder<static>|Tenant query()
 * @method static Builder<static>|Tenant representational()
 *
 * @mixin \Eloquent
 */
#[WithoutTimestamps]
class Tenant extends Model
{
    use HasFactory, HasRelationships, Searchable;

    #[\Override]
    protected $guarded = [];

    #[\Override]
    protected function casts(): array
    {
        return [
            'type' => TenantType::class,
        ];
    }

    #[\Override]
    protected static function booted()
    {
        static::saved(function ($tenant): void {
            // Clear homepage cache when tenant content changes
            Cache::tags(['homepage', "tenant_{$tenant->id}"])->flush();
        });
    }

    /**
     * The single VU SA central-office tenant.
     *
     * This lookup was hand-written in six places (controllers and Form Requests alike); it is
     * cheap but it is also the sort of thing that should have exactly one spelling.
     */
    public static function main(): ?self
    {
        return static::query()->where('type', TenantType::Pagrindinis)->first();
    }

    /**
     * Tenants that take part in student representation — everything except PKP.
     *
     * @param  Builder<static>  $query
     */
    public function scopeRepresentational($query): void
    {
        $query->whereIn('type', TenantType::representationalValues());
    }

    /**
     * Whether this tenant is the central office.
     */
    public function isMain(): bool
    {
        return $this->type === TenantType::Pagrindinis;
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function calendar(): HasMany
    {
        return $this->hasMany(Calendar::class);
    }

    public function duties(): HasManyThrough
    {
        return $this->hasManyThrough(Duty::class, Institution::class, 'tenant_id', 'institution_id');
    }

    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function quickLinks(): HasMany
    {
        return $this->hasMany(QuickLink::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function studySets(): HasMany
    {
        return $this->hasMany(StudySet::class);
    }

    public function users(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->duties(), (new Duty)->current_users());
    }

    public function reservations(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->reservations());
    }

    public function tenant()
    {
        return $this;
    }

    public function primary_institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'primary_institution_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * The subdomain this tenant's public site is served from.
     *
     * Subdomain and alias are the same except for the main tenant, whose 'vusa' alias is
     * served from 'www'.
     */
    public function subdomain(): string
    {
        return $this->alias === 'vusa' ? 'www' : $this->alias;
    }

    /**
     * The fully qualified host of this tenant's public site, e.g. 'mif.vusa.lt'.
     *
     * The apex is derived from app.url the same way the public routes do
     * (see routes/web.php), so local ('vusa.test') and production ('vusa.lt') both work.
     * Used to scope analytics to a single tenant, since Umami records the hostname on
     * every event.
     */
    public function publicHostname(): string
    {
        $apex = explode('.', parse_url(config('app.url'), PHP_URL_HOST) ?: '', 2)[1] ?? '';

        return $this->subdomain().'.'.$apex;
    }
}
