<?php

namespace App\Actions\ActivityRequests;

use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GetInstitutionActivityRequestHistory
{
    /** @return Builder<InstitutionActivityRequest> */
    private function query(Institution $institution, User $user): Builder
    {
        return InstitutionActivityRequest::query()->where('institution_id', $institution->id)
            ->when(! $user->can('askAboutActivity', [InstitutionCheckIn::class, $institution]), fn ($query) => $query->where('recipient_id', $user->id));
    }

    public function visible(Institution $institution, User $user): bool
    {
        return $user->can('askAboutActivity', [InstitutionCheckIn::class, $institution]) || $this->query($institution, $user)->exists();
    }

    public function execute(Institution $institution, User $user, int $page = 1): array
    {
        $batches = $this->query($institution, $user)->select('send_id')->selectRaw('MAX(created_at) AS sent_at')
            ->groupBy('send_id')->orderByDesc('sent_at')->orderByDesc('send_id')->paginate(20, page: max(1, $page));
        $requests = $this->query($institution, $user)->whereIn('send_id', $batches->getCollection()->pluck('send_id'))
            ->with(['recipient:id,name', 'requestedBy:id,name', 'meetings:id,start_time', 'checkIns', 'meeting:id,start_time', 'checkIn'])->get()->groupBy('send_id');

        return ['data' => $batches->getCollection()->map(fn ($batch) => [
            'id' => $batch->send_id,
            'campaigns' => $requests->get($batch->send_id, collect())->groupBy('campaign_type')->map(fn ($campaignRequests) => $campaignRequests->map(fn (InstitutionActivityRequest $request) => [
                'id' => $request->id, 'campaign_type' => $request->campaign_type->value,
                'requester' => $request->requestedBy?->name, 'automatic' => $request->requested_by_id === null,
                'recipient' => $request->recipient?->name, 'period_start' => $request->period_start->toDateString(),
                'period_end' => $request->period_end?->toDateString(), 'legacy' => $request->period_end === null,
                'note' => $request->note, 'answer' => $request->answer?->value,
                'created_at' => $request->created_at?->toISOString(), 'answered_at' => $request->answered_at?->toISOString(),
                'resolved_at' => $request->resolved_at?->toISOString(), 'expires_at' => $request->expires_at->toISOString(),
                'resolution_source' => $request->resolution_source,
                'status' => match (true) {
                    $request->answered_at !== null => 'answered', $request->resolved_at !== null => 'resolved', $request->expires_at->isPast() => 'expired', default => 'pending'
                },
                'meetings' => $request->meetings->merge($request->meeting === null ? [] : [$request->meeting])->unique('id')->map(fn ($meeting) => [
                    'id' => $meeting->id, 'date' => $meeting->start_time->toDateString(), 'url' => $user->can('view', $meeting) ? route('meetings.show', $meeting) : null,
                ])->values()->all(),
                'check_ins' => $request->checkIns->merge($request->checkIn === null ? [] : [$request->checkIn])->unique('id')->map(fn ($checkIn) => [
                    'id' => $checkIn->id, 'start' => $checkIn->start_date->toDateString(), 'end' => $checkIn->end_date->toDateString(),
                ])->values()->all(),
            ])->values()->all())->all(),
        ])->values()->all(), 'next_page' => $batches->hasMorePages() ? $batches->currentPage() + 1 : null];
    }
}
