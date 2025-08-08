<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Meeting;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for Meeting model authorization
 */
class MeetingPolicy extends ModelPolicy
{
    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::MEETING()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  Meeting  $meeting
     */
    public function view(User $user, Model $meeting): bool
    {
        // Check if user is a participant in the meeting
        if ($meeting->users->contains('id', $user->id)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::READ()->label);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  Meeting  $meeting
     */
    public function update(User $user, Model $meeting): bool
    {
        // Note: Meeting model doesn't have organizer_id field
        // Check if user is a participant in the meeting
        if ($meeting->users->contains('id', $user->id)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::UPDATE()->label);
    }

    /**
     * Determine whether the user can add participants to the meeting.
     *
     * @param  Meeting  $meeting
     */
    public function addParticipants(User $user, Model $meeting): bool
    {
        // Note: Meeting model doesn't have organizer_id field
        // Check if user is a participant in the meeting (can manage participants)
        if ($meeting->users->contains('id', $user->id)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::UPDATE()->label);
    }
}
