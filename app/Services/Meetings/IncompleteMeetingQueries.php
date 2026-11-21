<?php

namespace App\Services\Meetings;

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Vote;
use App\Services\InstitutionScopeResolver;
use Illuminate\Database\Eloquent\Builder;

class IncompleteMeetingQueries
{
    /** @param  Builder<Meeting>  $meetings */
    public static function constrain(Builder $meetings): void
    {
        $meetings->whereHas('agendaItems', fn (Builder $items) => $items
            ->where(fn (Builder $incomplete) => $incomplete
                ->whereNull('agenda_items.type')
                ->orWhere(fn (Builder $voting) => $voting
                    ->whereNotIn('agenda_items.type', AgendaItemType::voteFreeValues())
                    ->where(fn (Builder $missing) => $missing
                        ->whereDoesntHave('votes')
                        ->orWhereHas('votes', self::incompleteOutcomeVote(...))))));
    }

    private static function incompleteOutcomeVote(Builder $votes): void
    {
        // The first marked vote wins, even on historical items with multiple main votes.
        $mainVote = Vote::query()->select('id')
            ->whereColumn('votes.agenda_item_id', 'agenda_items.id')
            ->where('is_main', true)->orderBy('order')->limit(1);
        $institutions = Institution::query()->select('institutions.id')
            ->join('institution_meeting', 'institution_meeting.institution_id', '=', 'institutions.id')
            ->whereColumn('institution_meeting.meeting_id', 'agenda_items.meeting_id');
        $externalInstitutions = app(InstitutionScopeResolver::class)->constrainToScopes(
            clone $institutions,
            array_values(array_filter(InstitutionScope::cases(), fn (InstitutionScope $scope) => $scope->isExternal())),
        );

        $votes->where(fn (Builder $outcome) => $outcome
            ->where('votes.id', '=', $mainVote)
            ->orWhereNotExists($mainVote))
            ->where(fn (Builder $missing) => $missing
                ->whereNull('votes.decision')->orWhereIn('votes.decision', ['', '0'])
                ->orWhere(fn (Builder $perspective) => $perspective
                    ->where(fn (Builder $required) => $required->whereExists($externalInstitutions)->orWhereNotExists($institutions))
                    ->where(fn (Builder $fields) => $fields
                        ->whereNull('votes.student_vote')->orWhereIn('votes.student_vote', ['', '0'])
                        ->orWhereNull('votes.student_benefit')->orWhereIn('votes.student_benefit', ['', '0']))));
    }
}
