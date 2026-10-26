<?php

namespace App\Support\ActivityRequests;

use App\Models\InstitutionActivityRequest;
use App\Models\User;

class ActivityRequestHistoryMapper
{
    public const array RELATIONS = [
        'recipient:id,name', 'requestedBy:id,name', 'meetings:id,start_time', 'checkIns',
        'meeting:id,start_time', 'checkIn', 'resolvedByRequest:id,recipient_id', 'resolvedByRequest.recipient:id,name',
    ];

    public function map(InstitutionActivityRequest $request, User $user): array
    {
        return [
            'id' => $request->id,
            'campaign_type' => $request->campaign_type->value,
            'requester' => $request->requestedBy?->name,
            'automatic' => $request->requested_by_id === null,
            'recipient' => $request->recipient?->name,
            'period_start' => $request->period_start->toDateString(),
            'period_end' => $request->period_end?->toDateString(),
            'legacy' => $request->period_end === null,
            'note' => $request->note,
            'answer' => $request->answer?->value,
            'created_at' => $request->created_at?->toISOString(),
            'answered_at' => $request->answered_at?->toISOString(),
            'resolved_at' => $request->resolved_at?->toISOString(),
            'expires_at' => $request->expires_at->toISOString(),
            'resolution_source' => $request->resolution_source,
            'resolved_by' => $request->resolvedByRequest?->recipient?->name,
            'status' => match (true) {
                $request->answered_at !== null => 'answered',
                $request->resolved_at !== null => 'resolved',
                $request->expires_at->isPast() => 'expired',
                default => 'pending',
            },
            'meetings' => $request->meetings->merge($request->meeting === null ? [] : [$request->meeting])->unique('id')->map(fn ($meeting) => [
                'id' => $meeting->id,
                'date' => $meeting->start_time->toDateString(),
                'url' => $user->can('view', $meeting) ? route('meetings.show', $meeting) : null,
            ])->values()->all(),
            'check_ins' => $request->checkIns->merge($request->checkIn === null ? [] : [$request->checkIn])->unique('id')->map(fn ($checkIn) => [
                'id' => $checkIn->id,
                'start' => $checkIn->start_date->toDateString(),
                'end' => $checkIn->end_date->toDateString(),
            ])->values()->all(),
        ];
    }

    public function forRecipientHistory(InstitutionActivityRequest $request, User $viewer): array
    {
        $institution = $request->institution;

        return [
            ...$this->map($request, $viewer),
            'institution' => [
                'id' => $request->institution_id,
                'name' => $institution?->name,
                'url' => $institution !== null && $viewer->can('view', $institution) ? route('institutions.show', $institution) : null,
            ],
            'answer_url' => $viewer->id === $request->recipient_id && $request->isOpen() && $institution !== null
                ? $request->answerUrl() : null,
        ];
    }
}
