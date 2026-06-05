<?php

namespace App\Services;

use App\Enums\AgendaItemType;
use App\Models\Meeting;

class MeetingCompletionService
{
    /**
     * Calculate the completion status of a meeting based on its agenda items and votes.
     *
     * @return string 'complete'|'incomplete'|'no_items'
     */
    public function calculate(Meeting $meeting): string
    {
        if (! $meeting->relationLoaded('agendaItems')) {
            $meeting->load('agendaItems.votes');
        }

        $agendaItems = $meeting->agendaItems;

        if ($agendaItems->isEmpty()) {
            return 'no_items';
        }

        $allComplete = $agendaItems->every(function ($item) {
            $type = $item->getAttribute('type');
            if ($type instanceof AgendaItemType && $type->value === 'informational') {
                return true;
            }

            if (! $item->relationLoaded('votes')) {
                $item->load('votes');
            }

            if ($item->votes->isEmpty()) {
                return false;
            }

            $mainVote = $item->votes->firstWhere('is_main', true);
            if ($mainVote) {
                return ! empty($mainVote->student_vote)
                    && ! empty($mainVote->decision)
                    && ! empty($mainVote->student_benefit);
            }

            return $item->votes->contains(function ($vote) {
                return ! empty($vote->student_vote)
                    && ! empty($vote->decision)
                    && ! empty($vote->student_benefit);
            });
        });

        return $allComplete ? 'complete' : 'incomplete';
    }
}
