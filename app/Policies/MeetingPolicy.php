<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Meeting;
use App\Models\User;
use App\Services\InstitutionAccessService;
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
    public function __construct(ModelAuthorizer $authorizer, protected InstitutionAccessService $institutionAccessService)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::MEETING->label());
    }

    /**
     * Every admin may open the collection: the scoped search key always carries public meetings,
     * and adds their padalinys and own institutions when their permissions allow.
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * The read-only record: anyone may read a public meeting's agenda, while files, tasks and
     * discussion stay behind `view()`.
     *
     * @param  Meeting  $meeting
     */
    public function viewSummary(User $user, Model $meeting): bool
    {
        return $meeting->is_public || $this->view($user, $meeting);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  Meeting  $meeting
     */
    #[\Override]
    public function view(User $user, Model $meeting): bool
    {
        if ($meeting->hadMemberAtTheTime($user)) {
            return true;
        }

        // Check if user has access via institution relationships or coordinator access
        $meetingInstitutionIds = $meeting->institutions->pluck('id');
        if ($this->institutionAccessService->canAccessMeetingViaRelationships($user, $meetingInstitutionIds)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::READ->label());
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  Meeting  $meeting
     */
    #[\Override]
    public function update(User $user, Model $meeting): bool
    {
        // Note: Meeting model doesn't have organizer_id field
        if ($meeting->hadMemberAtTheTime($user)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::UPDATE->label());
    }

    /**
     * Determine whether the user can add participants to the meeting.
     *
     * @param  Meeting  $meeting
     */
    public function addParticipants(User $user, Model $meeting): bool
    {
        if ($meeting->hadMemberAtTheTime($user)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::UPDATE->label());
    }
}
