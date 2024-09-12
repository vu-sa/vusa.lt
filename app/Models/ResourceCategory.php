<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

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
