<?php

namespace App\Listeners;

use App\Enums\InstitutionActivityCampaign;
use App\Events\MeetingFullyCreated;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use Illuminate\Events\Dispatcher;

class ResolveInstitutionActivityRequests
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MeetingFullyCreated::class, function (MeetingFullyCreated $event): void {
            InstitutionActivityRequest::query()->open()
                ->where('campaign_type', InstitutionActivityCampaign::ActivityConfirmation)
                ->whereIn('institution_id', $event->meeting->institutions()->pluck('institutions.id'))
                ->whereDate('period_start', '<=', $event->meeting->start_time)
                ->where(fn ($query) => $query->whereDate('period_end', '>=', $event->meeting->start_time)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('period_end')->whereDate('period_start', '<=', today())->whereRaw('? <= ?', [$event->meeting->start_time->toDateString(), today()->toDateString()])))
                ->update(['resolved_at' => now(), 'resolution_source' => 'meeting', 'meeting_id' => $event->meeting->id]);
        });
        $events->listen('eloquent.created: '.InstitutionCheckIn::class, function (InstitutionCheckIn $checkIn): void {
            InstitutionActivityRequest::query()->open()
                ->where('campaign_type', InstitutionActivityCampaign::ActivityConfirmation)
                ->where('institution_id', $checkIn->institution_id)
                ->whereDate('period_start', '>=', $checkIn->start_date)
                ->where(fn ($query) => $query->whereDate('period_end', '<=', $checkIn->end_date)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('period_end')->whereRaw('? >= ?', [$checkIn->end_date->toDateString(), today()->toDateString()])))
                ->update(['resolved_at' => now(), 'resolution_source' => 'check_in', 'check_in_id' => $checkIn->id]);
        });
    }
}
