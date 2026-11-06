<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingAccessService;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MeetingPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer, private readonly MeetingAccessService $meetingAccess)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::MEETING->label());
    }

    /** The scoped search key filters public meetings and every additional viewing path. */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Coordinators hold `meetings.create.padalinys`; a representative holds `.own`, which
     * {@see createFor()} narrows to the institutions of the duties that grant it.
     */
    #[\Override]
    public function create(User $user): bool
    {
        return parent::create($user) || $this->authorizer->allows($user, 'meetings.create.own');
    }

    /**
     * Whether the user may record a meeting of this institution.
     */
    public function createFor(User $user, Institution $institution): bool
    {
        if ($this->authorizer->scope($user, 'meetings.create.padalinys')->allowsTenant($institution->tenant)) {
            return true;
        }

        return $this->authorizer->duties($user, 'meetings.create.own')->contains('institution_id', $institution->getKey());
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

    /** @var array<string, bool> */
    private array $hadMemberMemo = [];

    private function hadMemberAtTheTime(Meeting $meeting, User $user): bool
    {
        $key = "{$meeting->getKey()}:{$user->getKey()}";

        return $this->hadMemberMemo[$key] ??= $meeting->hadMemberAtTheTime($user);
    }

    /** @param Meeting $meeting */
    #[\Override]
    public function view(User $user, Model $meeting): bool
    {
        return $this->meetingAccess->hasBaselineAccess($user, $meeting)
            || $this->meetingAccess->allows($user, $meeting, 'meetings', 'read');
    }

    /** @param Meeting $meeting */
    #[\Override]
    public function update(User $user, Model $meeting): bool
    {
        if ($this->hadMemberAtTheTime($meeting, $user)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::UPDATE->label());
    }

    public function detachInstitution(User $user, Meeting $meeting, Institution $institution): bool
    {
        $remaining = $meeting->institutions()->whereKeyNot($institution->id)->with('tenant')->get();
        $date = $meeting->start_time->toDateString();
        $wasMember = $user->duties()->whereIn('duties.institution_id', $remaining->modelKeys())
            ->whereDate('dutiables.start_date', '<=', $date)
            ->where(fn ($query) => $query->whereNull('dutiables.end_date')->orWhereDate('dutiables.end_date', '>=', $date))
            ->exists();

        // Evaluate the remaining institutions without cached membership or own-model ids.
        $after = clone $meeting;
        $after->setRelation('institutions', $remaining);
        $after->setRelation('tenants', new Collection($remaining->pluck('tenant')->filter()->unique('id')->all()));

        return $wasMember || $this->meetingAccess->allows($user, $after, 'meetings', 'update');
    }

    /** @param Meeting $meeting */
    #[\Override]
    public function restore(User $user, Model $meeting): bool
    {
        // Duty::meetings excludes trashed rows; restoration checks the institution instead.
        return $this->meetingAccess->allows($user, $meeting, 'meetings', 'delete');
    }

    /** @param Meeting $meeting */
    public function addParticipants(User $user, Model $meeting): bool
    {
        if ($this->hadMemberAtTheTime($meeting, $user)) {
            return true;
        }

        return $this->commonChecker($user, $meeting, CRUDEnum::UPDATE->label());
    }
}
