<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
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
     * The meeting's `update` is the real gate; callers pair this with it. Holding that permission, not
     * merely having sat on the meeting, is what opens agenda editing.
     */
    #[\Override]
    public function create(User $user): bool
    {
        return parent::create($user) || collect(['own', 'padalinys', '*'])
            ->contains(fn (string $scope): bool => $this->authorizer->allows($user, "meetings.update.{$scope}"));
    }

    /**
     * Whoever holds a meeting-edit permission for the meeting edits its agenda items.
     */
    #[\Override]
    public function update(User $user, Model $agendaItem): bool
    {
        return $this->editsMeeting($user, $agendaItem) || parent::update($user, $agendaItem);
    }

    #[\Override]
    public function delete(User $user, Model $agendaItem): Response|bool
    {
        return $this->editsMeeting($user, $agendaItem) || parent::delete($user, $agendaItem);
    }

    public function viewSummary(User $user, Model $agendaItem): bool
    {
        return (bool) $agendaItem->meeting?->is_public || $this->view($user, $agendaItem);
    }

    private function editsMeeting(User $user, Model $agendaItem): bool
    {
        $meeting = $agendaItem->meeting;

        return $meeting !== null && $this->commonChecker($user, $meeting, CRUDEnum::UPDATE->label(), 'meetings');
    }
}
