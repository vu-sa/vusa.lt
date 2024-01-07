<?php

namespace App\Models\Pivots;

use App\Models\Contact;
use App\Models\Duty;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Dutiable extends MorphPivot
{
    use HasRelationships, HasUlids;

    protected $table = 'dutiables';

    protected $guarded = [];

    protected $dispatchesEvents = [
        'saved' => \App\Events\DutiableChanged::class,
        'deleted' => \App\Events\DutiableChanged::class,
    ];

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
        return $this->belongsTo(User::class, 'dutiable_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'dutiable_id');
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->duty(), (new Duty)->padaliniai());
    }
}
