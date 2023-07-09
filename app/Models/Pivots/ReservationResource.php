<?php

namespace App\Models\Pivots;

use App\Models\Interfaces\Decidable;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Traits\HasComments;
use App\Models\Traits\MakesDecisions;
use App\Services\ModelAuthorizer;
use App\States\ReservationResource\ReservationResourceState;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ReservationResource extends Pivot implements Decidable
{
    use MakesDecisions, HasComments;

    protected $guarded = [];

    protected $with = ['comments'];

    protected $appends = ['state_properties'];

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

    // Used for notification purposes, but maybe will need to be refactored
    public function users()
    {
        return $this->reservation->users();
    }

    public function approvable()
    {
        // if user null, return false
        if (! auth()->user()) {
            return false;
        }

        $authorizer = new ModelAuthorizer();

        if ($authorizer->forUser(auth()->user())->check(config('permission.resource_managership_indicating_permission'))) {
            // check if authorizer->getPadaliniai() contains $this->padalinys
            return $authorizer->getPadaliniai()->contains($this->resource->padalinys);
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
        abort(403, 'Negalima priimti tokio sprendimo.');
    }

    public function decisionToApprove()
    {
        if (! $this->authorizer->forUser(auth()->user())->check(config('permission.resource_managership_indicating_permission'))) {
            // throw authorization exception if user is not authorized
            abort(403, 'Neturite teisių patvirtinti rezervacijos veiksmams.');
        }

        $this->state->handleApprove();
    }

    public function decisionToReject()
    {
        if (! $this->authorizer->forUser(auth()->user())->check(config('permission.resource_managership_indicating_permission'))) {
            // throw authorization exception if user is not authorized
            abort(403, 'Neturite teisių atmesti rezervacijos veiksmams.');
        }

        $this->state->handleReject();
    }

    public function decisionToCancel()
    {
        if ($this->reservation->users()->where('users.id', auth()->id())->exists()) {

            $this->state->handleCancel();

            return;
        }

        abort(403, 'Negalite atšaukti rezervacijos veiksmų.');
    }
}
