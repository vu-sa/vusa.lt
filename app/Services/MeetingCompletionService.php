<?php

namespace App\Services;

use App\Enums\AgendaItemType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Vote;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class MeetingCompletionService
{
    /** @param EloquentCollection<int, Meeting> $meetings
     * @return EloquentCollection<int, Meeting>
     */
    public function incompleteInPeriod(EloquentCollection $meetings, CarbonInterface $start, CarbonInterface $end): EloquentCollection
    {
        $meetings = $meetings->filter(fn (Meeting $meeting) => $meeting->start_time->toDateString() >= $start->toDateString()
            && $meeting->start_time->toDateString() <= $end->toDateString());
        $meetings->loadMissing(['agendaItems.votes', 'institutions']);

        return $meetings->reject(fn (Meeting $meeting) => $this->calculate($meeting) === 'complete');
    }

    /**
     * Return the concrete work still required to complete a meeting agenda.
     *
     * @return array<int, array{
     *     type: 'agenda_missing'|'agenda_item_type_missing'|'agenda_item_vote_missing',
     *     agenda_item_id?: string,
     *     title?: string,
     *     position?: int,
     *     missing_fields?: array<int, 'decision'|'student_vote'|'student_benefit'>
     * }>
     */
    public function missingActions(Meeting $meeting): array
    {
        if (! $meeting->relationLoaded('agendaItems')) {
            $meeting->load('agendaItems.votes');
        }

        if ($meeting->agendaItems->isEmpty()) {
            return [['type' => 'agenda_missing']];
        }

        $requiresStudentPerspective = $meeting->requiresStudentPerspective();

        $actions = [];

        foreach ($meeting->agendaItems->sortBy('order')->values() as $index => $item) {
            $base = [
                'agenda_item_id' => (string) $item->getKey(),
                'title' => (string) $item->title,
                'position' => $index + 1,
            ];

            $type = $item->getAttribute('type');

            if ($type === null) {
                $actions[] = ['type' => 'agenda_item_type_missing', ...$base];

                continue;
            }

            if ($this->itemIsComplete($item, $requiresStudentPerspective)) {
                continue;
            }

            $mainVote = $item->votes->firstWhere('is_main', true);
            $vote = $mainVote ?? $item->votes->first();
            $requiredFields = $requiresStudentPerspective
                ? ['decision', 'student_vote', 'student_benefit']
                : ['decision'];
            $missingFields = collect($requiredFields)
                ->filter(fn (string $field): bool => $vote === null || empty($vote->{$field}))
                ->values()
                ->all();

            $actions[] = [
                'type' => 'agenda_item_vote_missing',
                ...$base,
                'missing_fields' => $missingFields,
            ];
        }

        return $actions;
    }

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

        $allComplete = $agendaItems->every(
            fn (AgendaItem $item): bool => $this->itemIsComplete($item, $requiresStudentPerspective)
        );

        return $allComplete ? 'complete' : 'incomplete';
    }

    /**
     * The one rule for a filled-in agenda item, shared by the meeting status, the completion task
     * and the agenda item search index: a set type, and for voting items a complete main vote.
     */
    public function itemIsComplete(AgendaItem $item, bool $requiresStudentPerspective): bool
    {
        $type = $item->getAttribute('type');

        if (! $type instanceof AgendaItemType) {
            return false;
        }

        if (! $type->requiresVote()) {
            return true;
        }

        $mainVote = $item->votes->firstWhere('is_main', true);

        return $mainVote instanceof Vote && $this->voteIsComplete($mainVote, $requiresStudentPerspective);
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
     * @param  Collection<int, Institution>|EloquentCollection<int, Institution>  $institutions
     */
    public function institutionsRequireStudentPerspective($institutions): bool
    {
        if ($institutions->isEmpty()) {
            return true;
        }

        return $institutions->contains(fn (Institution $institution) => $institution->governance_scope->isExternal());
    }
}
