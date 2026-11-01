<?php

namespace App\Http\Resources;

use App\Models\Meeting;
use App\Services\AgendaItemPresenter;
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
        $outcome = $this->outcomeSummary($request->user());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_time' => $this->start_time->toISOString(),
            'type' => $this->type?->value,
            'agenda_items_count' => $this->agenda_items_count,
            'agenda_item_titles' => $this->agendaItems
                ->sortBy('order')
                ->take(3)
                ->map(fn ($item) => AgendaItemPresenter::forUser($item, $request->user())['title'])
                ->values()
                ->all(),
            'vote_matches' => $outcome['outcome_aligned'],
            'vote_mismatches' => $outcome['outcome_misaligned'],
            'incomplete_vote_data' => $outcome['outcome_unknown'],
            'has_protocol' => $this->has_protocol,
            'has_report' => $this->has_report,
            'can_discard' => $this->isDiscardable(),
        ];
    }
}
