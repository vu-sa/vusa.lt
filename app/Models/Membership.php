<?php

namespace App\Models;

use App\Models\Pivots\MembershipUser;
use App\Models\Pivots\Trainable;
use App\Models\Traits\HasTranslations;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property array|string $name
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MembershipUser|Trainable|null $pivot
 * @property-read Collection<int, Training> $availableTrainings
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 * @property-read Collection<int, User> $users
 *
 * @method static \Database\Factories\MembershipFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory, HasTranslations, HasUlids, Searchable;

    public $translatable = [
        'name',
    ];

    protected $fillable = [
        'name',
        'tenant_id',
    ];

    public function toSearchableArray(): array
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->using(MembershipUser::class)->withTimestamps()->withPivot('start_date', 'end_date');
    }

    public function availableTrainings()
    {
        return $this->morphToMany(Training::class, 'trainable')->using(Trainable::class);
    }
}
