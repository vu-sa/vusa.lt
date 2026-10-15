<?php

namespace App\Listeners;

use App\Events\MeetingFullyCreated;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use Illuminate\Events\Dispatcher;

/**
 * Re-indexes an institution when a meeting or check-in changes its activity status, so the
 * Institucijos "Aktyvumas" filter agrees with the ViSAK overview numbers that link to it.
 */
class SyncInstitutionActivityIndex
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MeetingFullyCreated::class, fn (MeetingFullyCreated $event) => $this->meetingChanged($event->meeting));
        $events->listen('eloquent.updated: '.Meeting::class, function (Meeting $meeting): void {
            if ($meeting->wasChanged('start_time')) {
                $this->meetingChanged($meeting);
            }
        });
        $events->listen('eloquent.deleted: '.Meeting::class, fn (Meeting $meeting) => $this->meetingChanged($meeting));
        $events->listen('eloquent.restored: '.Meeting::class, fn (Meeting $meeting) => $this->meetingChanged($meeting));

        $events->listen('eloquent.saved: '.InstitutionCheckIn::class, fn (InstitutionCheckIn $checkIn) => $this->checkInChanged($checkIn));
        $events->listen('eloquent.deleted: '.InstitutionCheckIn::class, fn (InstitutionCheckIn $checkIn) => $this->checkInChanged($checkIn));
    }

    private function meetingChanged(Meeting $meeting): void
    {
        $meeting->institutions()->get()->searchable();
    }

    private function checkInChanged(InstitutionCheckIn $checkIn): void
    {
        $checkIn->institution?->searchable();
    }
}
