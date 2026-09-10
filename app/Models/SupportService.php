<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\SupportServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property array|string $name
 * @property string $slug
 * @property int $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SupportRequestArea> $areas
 * @property-read array $translatable_columns_from
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\SupportServiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportService whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportService whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportService whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportService whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'slug', 'is_active', 'sort_order'])]
class SupportService extends Model
{
    /** @use HasFactory<SupportServiceFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    public function areas(): HasMany
    {
        return $this->hasMany(SupportRequestArea::class);
    }
}
