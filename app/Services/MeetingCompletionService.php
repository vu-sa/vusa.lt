<?php

namespace App\Services;

use App\Enums\AgendaItemType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Vote;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use Illuminate\Support\Collection;

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

        $requiresStudentPerspective = $meeting->requiresStudentPerspective();

        $allComplete = $agendaItems->every(function ($item) use ($requiresStudentPerspective) {
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
                return $this->voteIsComplete($mainVote, $requiresStudentPerspective);
            }

            return $item->votes->contains(fn ($vote) => $this->voteIsComplete($vote, $requiresStudentPerspective));
        });

        return $allComplete ? 'complete' : 'incomplete';
    }

    /**
     * A VU SA body's vote is complete once it has an outcome: there is no separate student
     * position to record when the representatives *are* the organisation.
     *
     * Public so the agenda completion task counts progress by the same rule the meeting's
     * own completion status uses ({@see AgendaCompletionTaskHandler}).
     */
    public function voteIsComplete(Vote $vote, bool $requiresStudentPerspective): bool
    {
        if (empty($vote->decision)) {
            return false;
        }

        return ! $requiresStudentPerspective
            || (! empty($vote->student_vote) && ! empty($vote->student_benefit));
    }

    /**
     * Which institutions' meetings ask for the student-perspective vote fields.
     *
     * Kept here rather than on the model so the rule ("one external body is enough") lives in
     * one place — a joint VU/VU SA meeting still records how the students voted.
     *
     * @param  Collection<int, Institution>|\Illuminate\Database\Eloquent\Collection<int, Institution>  $institutions
     */
    public function institutionsRequireStudentPerspective($institutions): bool
    {
        if ($institutions->isEmpty()) {
            return true;
        }

        return $institutions->contains(fn (Institution $institution) => $institution->governance_scope->isExternal());
    }
}
