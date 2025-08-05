<?php

namespace App\Models\Pivots;

use App\Models\Interfaces\Decidable;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Traits\HasComments;
use App\Models\Traits\MakesDecisions;
use App\Services\ModelAuthorizer;
use App\States\ReservationResource\ReservationResourceState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property string $reservation_id
 * @property string $resource_id
 * @property \Illuminate\Support\Carbon|null $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property int $quantity
 * @property ReservationResourceState $state
 * @property string|null $returned_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read bool $approvable
 * @property-read mixed $state_properties
 * @property-read Reservation $reservation
 * @property-read resource $resource
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationResource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationResource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationResource query()
 * @mixin \Eloquent
 */
class ReservationResource extends Pivot implements Decidable
{
    use HasComments, MakesDecisions;

    protected $guarded = [];

    protected $with = ['comments'];

    protected $appends = ['state_properties'];

    protected $dispatchesEvents = [
        'created' => \App\Events\ReservationResourceCreated::class,
    ];

    protected $casts = [
        'state' => ReservationResourceState::class,
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function resource(): BelongsTo
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

        $authorizer = app(ModelAuthorizer::class);

        if ($authorizer->forUser(auth()->user())->check(config('permission.resource_managership_indicating_permission'))) {
            // check if authorizer->getTenants() contains $this->tenants
            return $authorizer->getTenants()->contains($this->resource->tenant);
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
