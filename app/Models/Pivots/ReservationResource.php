<?php

namespace App\Models\Pivots;

use App\Models\Interfaces\Decidable;
use App\Models\Reservation;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasDecisions;
use App\Models\Traits\MakesDecisions;
use App\Services\ModelAuthorizer;
use App\States\ReservationResource\ReservationResourceState;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ReservationResource extends Pivot implements Decidable
{
    use MakesDecisions, HasComments;

    protected $guarded = [];

    protected $with = ['comments'];

    protected $appends = ['approvable', 'state_properties'];

    protected $casts = [
        'state' => ReservationResourceState::class,
        'start_time' => 'datetime',
        'end_time' => 'datetime',
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

        if ($authorizer->forUser(auth()->user())->check('reservations.update.padalinys')) {
            // check if authorizer->getPadaliniai() contains $this->padalinys
            return $authorizer->getPadaliniai()->contains($this->padalinys);
        }

        return false;
    }

    public function getApprovableAttribute(): bool
    {
        return $this->approvable();
    }

    public function getStatePropertiesAttribute()
    {
        return [
            'tagType' => $this->state->tagType(),
            'description' => $this->state->description(),
        ];
    }

    public function decisionToProgress()
    {
        abort('Negalima priimti tokio sprendimo.', 403);
    }

    public function decisionToApprove()
    {
        if(!$this->authorizer->forUser(auth()->user())->check('reservations.update.padalinys')) {
            // throw authorization exception if user is not authorized
            abort('Neturite teisių patvirtinti rezervacijos veiksmams.', 403);
        }

        $this->state->handleApprove();
    }

    public function decisionToReject()
    {
        if(!$this->authorizer->forUser(auth()->user())->check('reservations.update.padalinys')) {
            // throw authorization exception if user is not authorized
            abort('Neturite teisių atmesti rezervacijos veiksmams.', 403);
        }

        $this->state->handleReject();
    }

    public function decisionToCancel()
    {
        if ($this->reservation()->users()->where('users.id', auth()->id())->exists()) {

            $this->state->handleCancel();
            return;
        }

        abort('Negalite atšaukti rezervacijos veiksmų.', 403);
    }
}
