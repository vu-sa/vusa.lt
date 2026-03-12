<?php

namespace App\Models;

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
 * @property string|null $type
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
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read int|null $reservations_count
 *
 * @method static \Database\Factories\TenantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant query()
 *
 * @mixin \Eloquent
 */
class Tenant extends Model
{
    use HasFactory, HasRelationships, Searchable;

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted()
    {
        static::saved(function ($tenant) {
            // Clear homepage cache when tenant content changes
            Cache::tags(['homepage', "tenant_{$tenant->id}"])->flush();
        });
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
}
