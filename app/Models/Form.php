<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property array|string $name
 * @property array|string|null $description
 * @property string|null $user_id
 * @property int $tenant_id
 * @property array|string|null $path URL path for visible forms
 * @property string|null $publish_time
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FormField> $formFields
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Registration> $registrations
 * @property-read \App\Models\Tenant $tenant
 * @property-read \App\Models\Training|null $training
 * @property-read mixed $translations
 * @property-read \App\Models\User|null $user
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
 * @mixin \Eloquent
 */
class Form extends Model
{
    /** @use HasFactory<\Database\Factories\FormFactory> */
    use HasFactory, HasTranslations, HasUlids, Searchable, SoftDeletes;

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

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
            'path->'.app()->getLocale() => $this->getTranslation('path', app()->getLocale()),
        ];
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
