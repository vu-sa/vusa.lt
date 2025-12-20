<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Meeting;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\RelationshipService;
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

        // Check if user has access via authorized institution relationships
        if ($this->hasAuthorizedRelationshipAccess($user, $meeting)) {
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

    /**
     * Check if user has access to view meeting via authorized institution relationships.
     *
     * This allows users who have authorized outgoing relationships to institutions
     * that own the meeting to view those meetings.
     */
    protected function hasAuthorizedRelationshipAccess(User $user, Model $meeting): bool
    {
        // Get the user's institutions (via their duties)
        $userInstitutions = $user->duties()
            ->whereNotNull('institution_id')
            ->with('institution')
            ->get()
            ->pluck('institution')
            ->filter();

        if ($userInstitutions->isEmpty()) {
            return false;
        }

        // Get the meeting's institutions
        $meetingInstitutionIds = $meeting->institutions->pluck('id')->toArray();

        if (empty($meetingInstitutionIds)) {
            return false;
        }

        // For each of the user's institutions, check if any of the meeting's institutions
        // are in the authorized related institutions
        foreach ($userInstitutions as $userInstitution) {
            $relatedInstitutions = RelationshipService::getRelatedInstitutions($userInstitution, authorizedOnly: true);

            if ($relatedInstitutions->pluck('id')->intersect($meetingInstitutionIds)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }
}
