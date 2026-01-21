<?php

namespace App\Models\Pivots;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Traits\HasApprovals;
use App\Models\Traits\HasComments;
use App\Services\ModelAuthorizer;
use App\States\ReservationResource\ReservationResourceState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read bool $approvable
 * @property-read mixed $state_properties
 * @property-read Reservation $reservation
 * @property-read resource $resource
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationResource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationResource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationResource query()
 *
 * @mixin \Eloquent
 */
class ReservationResource extends Pivot implements Approvable
{
    use HasApprovals, HasComments;

    /**
     * Indicates if the IDs are auto-incrementing.
     * Required for Pivot models with custom id column.
     */
    public $incrementing = true;

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The table associated with the model.
     */
    protected $table = 'reservation_resource';

    protected $guarded = [];

    protected $with = ['comments', 'approvals'];

    protected $appends = ['state_properties'];

    protected $dispatchesEvents = [
        'created' => \App\Events\ReservationResourceCreated::class,
    ];

    protected function casts(): array
    {
        return [
            'state' => ReservationResourceState::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

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

    /**
     * Check if the current user can approve this resource.
     */
    public function approvable(): bool
    {
        if (! auth()->user()) {
            return false;
        }

        return $this->canBeApprovedBy(auth()->user());
    }

    public function getApprovableAttribute(): bool
    {
        return $this->approvable();
    }

    public function getStatePropertiesAttribute()
    {
        if (! $this->state) {
            return [
                'tagType' => 'default',
                'description' => '',
            ];
        }

        return [
            'tagType' => $this->state->tagType(),
            'description' => $this->state->description(),
        ];
    }

    // =========================================================================
    // Approvable Contract Implementation
    // =========================================================================

    /**
     * Handle approval completion - triggers state transitions.
     */
    public function onApprovalComplete(ApprovalDecision $decision, int $step): void
    {
        match ($decision) {
            ApprovalDecision::Approved => $this->state->handleApprove(),
            ApprovalDecision::Rejected => $this->state->handleReject(),
            ApprovalDecision::Cancelled => $this->state->handleCancel(),
        };
    }

    /**
     * Get the users who can approve this resource at the given step.
     */
    public function getApproversForStep(int $step): Collection
    {
        // For ReservationResource, approvers are the resource managers
        return $this->resource->managers();
    }

    /**
     * Get the approval flow for this resource.
     * Falls back to global flow for ReservationResource type.
     */
    public function getApprovalFlow(): ?ApprovalFlow
    {
        // Try to find a global flow for ReservationResource
        return ApprovalFlow::query()
            ->where('flowable_type', self::class)
            ->whereNull('flowable_id')
            ->first();
    }

    /**
     * Get the display name for this approvable in notifications.
     */
    public function getApprovalDisplayName(): string
    {
        return $this->reservation->name.' - '.$this->resource->name;
    }

    /**
     * Get the URL for viewing this approvable.
     */
    public function getApprovalUrl(): string
    {
        return route('reservations.show', $this->reservation_id);
    }

    /**
     * Check if a decision is allowed for the current state.
     *
     * This prevents saving approvals for invalid state transitions.
     * Uses the state machine's transition configuration from ReservationResourceState.
     */
    public function isDecisionAllowed(ApprovalDecision $decision): bool
    {
        if (! $this->state) {
            return false;
        }

        // Map decisions to their target states
        $targetStateClass = match ($decision) {
            ApprovalDecision::Approved => $this->getApproveTargetState(),
            ApprovalDecision::Rejected => \App\States\ReservationResource\Rejected::class,
            ApprovalDecision::Cancelled => \App\States\ReservationResource\Cancelled::class,
        };

        if ($targetStateClass === null) {
            return false;
        }

        // Use Spatie's state machine to check if transition is allowed
        return $this->state->canTransitionTo($targetStateClass);
    }

    /**
     * Get the target state for an approve action based on current state.
     */
    protected function getApproveTargetState(): ?string
    {
        return match ($this->state->getValue()) {
            'created' => \App\States\ReservationResource\Reserved::class,
            'reserved' => \App\States\ReservationResource\Lent::class,
            'lent' => \App\States\ReservationResource\Returned::class,
            default => null,
        };
    }

    // =========================================================================
    // Partial quantity approval
    // =========================================================================

    /**
     * Update the quantity when a partial approval is made.
     * This allows resource managers to approve less than the requested quantity.
     */
    public function updateApprovedQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        if ($quantity > $this->quantity) {
            throw new \InvalidArgumentException('Approved quantity cannot exceed requested quantity.');
        }

        if ($quantity !== $this->quantity) {
            $this->update(['quantity' => $quantity]);
        }
    }

    // =========================================================================
    // Override canBeApprovedBy to include cancel permission for owners
    // =========================================================================

    /**
     * Check if a user can approve this model at the given step with the given decision.
     * Resource managers can approve/reject, owners can only cancel.
     *
     * @param  \App\Enums\ApprovalDecision|null  $decision  The decision being made (used for owner cancel check)
     */
    public function canBeApprovedBy(\App\Models\User $user, ?int $step = null, $decision = null): bool
    {
        // Check if user has resource management permission for this tenant (can approve/reject)
        $authorizer = app(ModelAuthorizer::class);

        if ($authorizer->forUser($user)->check(config('permission.resource_managership_indicating_permission'))) {
            if ($authorizer->getTenants()->contains($this->resource->tenant)) {
                return true;
            }
        }

        // Reservation owners can only cancel their own reservations
        if ($this->reservation->users()->where('users.id', $user->id)->exists()) {
            // Only allow if decision is cancel (or if decision not specified for compatibility)
            return $decision === null || $decision === \App\Enums\ApprovalDecision::Cancelled;
        }

        return false;
    }
}
