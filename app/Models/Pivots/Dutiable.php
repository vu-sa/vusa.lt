<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Dutiable extends MorphPivot
{
    use HasRelationships;
    
    protected $table = 'dutiables';

    protected $guarded = [];

    protected $with = ['duty'];

    protected $casts = [
        'extra_attributes' => AsArrayObject::class,
    ];

    // dutiable
    public function dutiable()
    {
        return $this->morphTo();
    }
    
    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->duty(), (new Duty)->padaliniai());
    }
}
