<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\SupportRequestAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $support_service_id
 * @property array|string $name
 * @property string $slug
 * @property int $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, SupportRequest> $requests
 * @property-read SupportService $service
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\SupportRequestAreaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestArea query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestArea whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestArea whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestArea whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportRequestArea whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
#[Fillable(['support_service_id', 'name', 'slug', 'is_active', 'sort_order'])]
class SupportRequestArea extends Model
{
    /** @use HasFactory<SupportRequestAreaFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(SupportService::class, 'support_service_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }
}
