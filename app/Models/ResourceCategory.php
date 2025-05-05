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
            'id' => (string) $this->id,
            'name->lt' => $this->getTranslation('name', 'lt'),
            'name->en' => $this->getTranslation('name', 'en'),
            'description->lt' => $this->getTranslation('description', 'lt'),
            'description->en' => $this->getTranslation('description', 'en'),
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }
}
