<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;


class DutyInstitution extends Model
{
    use HasFactory, HasRelationships;

    protected $table = 'duties_institutions';

    protected $guarded = [];

    protected $with = ['types'];

    protected $casts = [
        'attributes' => 'array',
    ];

    public function duties()
    {
        return $this->hasMany(Duty::class, 'institution_id');
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function padalinys() {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'institution_id');
    }

    public function users() 
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->users());
    }
}
