<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property array|string $name
 * @property array|string|null $description
 * @property string|null $icon
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resource> $resources
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 * @property-read mixed $translations
 * @method static \Database\Factories\ResourceCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResourceCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResourceCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResourceCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResourceCategory whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResourceCategory whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResourceCategory whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResourceCategory whereLocales(string $column, array $locales)
 * @mixin \Eloquent
 */
class ResourceCategory extends Model
{
    use HasFactory, HasRelationships, HasTranslations, Searchable;

    protected $guarded = [];

    public $translatable = ['name', 'description'];

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->resources(), (new Resource)->tenant());
    }

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
            'description->'.app()->getLocale() => $this->getTranslation('description', app()->getLocale()),
        ];
    }
}
