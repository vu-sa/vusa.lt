<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for AgendaItem model authorization.
 *
 * `view` is intentionally broad — mirroring MeetingPolicy::view() through the
 * item's meeting — so meeting participants, coordinators with the .padalinys
 * read scope, and related-institution viewers can open the agenda item page
 * (which is also its read-only "show" surface). `update` stays narrow.
 */
class AgendaItemPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer, protected InstitutionAccessService $institutionAccessService)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::AGENDA_ITEM->label());
    }

    /**
     * @param  AgendaItem  $agendaItem
     */
    #[\Override]
    public function view(User $user, Model $agendaItem): bool
    {
        $meeting = $agendaItem->meeting;

        if ($meeting) {
            $meeting->loadMissing('institutions');

            if ($meeting->hadMemberAtTheTime($user)) {
                return true;
            }

            if ($this->institutionAccessService->canAccessMeetingViaRelationships($user, $meeting->institutions->pluck('id'))) {
                return true;
            }
        }

        return $this->commonChecker($user, $agendaItem, CRUDEnum::READ->label(), $this->pluralModelName);
    }

    /**
     * Whether the user may add items anywhere, for menus; a store request asks {@see createFor()}.
     */
    #[\Override]
    public function create(User $user): bool
    {
        return parent::create($user) || collect(['own', 'padalinys', '*'])
            ->contains(fn (string $scope): bool => $this->authorizer->allows($user, "meetings.update.{$scope}"));
    }

    /**
     * Whether the user may add items to this meeting's agenda: whoever edits the meeting.
     */
    public function createFor(User $user, Meeting $meeting): bool
    {
        return $user->can('update', $meeting);
    }

    /**
     * Whoever edits the meeting — including a member at the time whose duty has since ended — edits its agenda items.
     *
     * @param  AgendaItem  $agendaItem
     */
    #[\Override]
    public function update(User $user, Model $agendaItem): bool
    {
        return $this->editsMeeting($user, $agendaItem) || parent::update($user, $agendaItem);
    }

    /** @param AgendaItem $agendaItem */
    #[\Override]
    public function delete(User $user, Model $agendaItem): Response|bool
    {
        return $this->editsMeeting($user, $agendaItem) || parent::delete($user, $agendaItem);
    }

    /** @param AgendaItem $agendaItem */
    public function viewSummary(User $user, Model $agendaItem): bool
    {
        return (bool) $agendaItem->meeting?->is_public || $this->view($user, $agendaItem);
    }

    /** @param AgendaItem $agendaItem */
    private function editsMeeting(User $user, Model $agendaItem): bool
    {
        $meeting = $agendaItem->meeting;

        return $meeting !== null && $user->can('update', $meeting);
    }
}
