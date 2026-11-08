<?php

namespace App\Http\Resources;

use App\Enums\MeetingType;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meeting
 */
class InstitutionMeetingResource extends JsonResource
{
    /** @return array{id: string, title: string, start_time: string, type: MeetingType|null, agenda_items_count: int, vote_matches: int, vote_mismatches: int, incomplete_vote_data: int, has_protocol: bool, has_report: bool, can_discard: bool} */
    #[\Override]
    public function toArray(Request $request): array
    {
        $outcome = $this->outcomeSummary($request->user());
        $agendaItemsCount = (int) ($this->agenda_items_count ?? $this->agendaItems->count());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_time' => $this->start_time->toISOString(),
            'type' => $this->type,
            'agenda_items_count' => $agendaItemsCount,
            'vote_matches' => $outcome['outcome_aligned'],
            'vote_mismatches' => $outcome['outcome_misaligned'],
            'incomplete_vote_data' => $outcome['outcome_unknown'],
            'has_protocol' => (bool) ($this->resource->getAttributes()['protocol_exists'] ?? $this->has_protocol),
            'has_report' => (bool) ($this->resource->getAttributes()['report_exists'] ?? $this->has_report),
            'can_discard' => $this->created_at->isAfter(now()->subDay()) && $agendaItemsCount === 0 && $this->isDiscardable(),
        ];
    }
}
