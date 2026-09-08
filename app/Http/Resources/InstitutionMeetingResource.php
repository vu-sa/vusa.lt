<?php

namespace App\Http\Resources;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meeting
 */
class InstitutionMeetingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $statistics = $this->voteStatistics();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_time' => $this->start_time->toISOString(),
            'type' => $this->type?->value,
            'agenda_items_count' => $this->agenda_items_count,
            'agenda_item_titles' => $this->agendaItems
                ->sortBy('order')
                ->take(3)
                ->pluck('title')
                ->values()
                ->all(),
            'vote_matches' => $statistics['vote_matches'],
            'vote_mismatches' => $statistics['vote_mismatches'],
            'incomplete_vote_data' => $statistics['incomplete_vote_data'],
            'has_protocol' => $this->has_protocol,
            'has_report' => $this->has_report,
        ];
    }
}
