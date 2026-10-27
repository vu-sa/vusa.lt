<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Services\MeetingAccessService;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Private agenda reading grants remain independent of meeting reading grants. */
class AgendaItemPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer, private readonly MeetingAccessService $meetingAccess)
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

        if ($meeting && $this->meetingAccess->hasBaselineAccess($user, $meeting)) {
            return true;
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
     * Meeting editors manage its agenda; a separate creation grant also needs viewing access.
     */
    public function createFor(User $user, Meeting $meeting): bool
    {
        return $user->can('update', $meeting)
            || ($user->can('viewSummary', $meeting) && $this->meetingAccess->allows($user, $meeting, 'agendaItems', 'create'));
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
