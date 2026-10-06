<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @deprecated Kept solely for deployed migrations; use InstitutionType or DutyType.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property string|null $model_type
 * @property string|null $slug
 * @property array<array-key, mixed>|null $extra_attributes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Duty> $duties
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, Institution> $institutions
 * @property-read RoleType|null $pivot
 * @property-read Collection<int, Role> $roles
 * @property-read mixed $translations
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
class Type extends Model
{
    use HasTranslations, SoftDeletes;

    protected $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return ['extra_attributes' => 'array'];
    }

    public function institutions(): MorphToMany
    {
        return $this->morphedByMany(Institution::class, 'typeable');
    }

    public function duties(): MorphToMany
    {
        return $this->morphedByMany(Duty::class, 'typeable');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->using(RoleType::class);
    }
}
