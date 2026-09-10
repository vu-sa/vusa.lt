<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\SupportRequestTypeFactory;
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
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, SupportRequest> $requests
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\SupportRequestTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestType whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestType whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestType whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestType whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'slug', 'is_active', 'sort_order'])]
class SupportRequestType extends Model
{
    /** @use HasFactory<SupportRequestTypeFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    public function requests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }
}
