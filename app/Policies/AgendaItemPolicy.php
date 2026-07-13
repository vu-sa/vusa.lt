<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
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
        $this->pluralModelName = Str::plural(ModelEnum::AGENDA_ITEM()->label);
    }

    /**
     * @param  AgendaItem  $agendaItem
     */
    public function view(User $user, Model $agendaItem): bool
    {
        $meeting = $agendaItem->meeting;

        if ($meeting) {
            $meeting->loadMissing(['users', 'institutions']);

            if ($meeting->users->contains('id', $user->id)) {
                return true;
            }

            if ($this->institutionAccessService->canAccessMeetingViaRelationships($user, $meeting->institutions->pluck('id'))) {
                return true;
            }
        }

        return $this->commonChecker($user, $agendaItem, CRUDEnum::READ()->label, $this->pluralModelName);
    }
}
