<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property array|string $name
 * @property array|string|null $description
 * @property int $tenant_id
 * @property array|string|null $path URL path for visible forms
 * @property Carbon|null $publish_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, FormField> $formFields
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, Registration> $registrations
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\FormFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Form withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory, HasTranslations, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'path',
        'publish_time',
    ];

    public $translatable = [
        'name',
        'description',
        'path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish_time' => 'datetime',
        ];
    }

    /**
     * @return HasMany<FormField, $this>
     */
    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
