<?php

namespace App\Models\Pivots;

use App\Models\Reservation;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasDecisions;
use App\Services\ModelAuthorizer;
use App\States\ReservationResource\ReservationResourceState;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ReservationResource extends Pivot
{
    use HasDecisions, HasComments;

    protected $guarded = [];

    protected $with = ['comments'];

    protected $appends = ['approvable'];

    protected $casts = [
        'state' => ReservationResourceState::class,
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->reservation(), (new Reservation)->padaliniai());
    }

    public function approvable()
    {
        // if user null, return false
        if (!auth()->user()) {
            return false;
        }

        $authorizer = new ModelAuthorizer();

        return $authorizer->forUser(auth()->user())->check('resources.update.padalinys');
    }

    public function getApprovableAttribute()
    {
        return $this->approvable();
    }
}
