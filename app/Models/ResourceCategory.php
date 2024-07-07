<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class ResourceCategory extends Model
{
    use HasFactory, HasRelationships, Searchable, HasTranslations;

    protected $guarded = [];

    public $translatable = ['name', 'description'];

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->resources(), (new Resource)->padalinys());
    }

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', 'lt'),
            'description->'.app()->getLocale() => $this->getTranslation('description', 'lt'),
        ];
    }
}
