<?php

namespace App\Services;

use App\Models\Vote;
use Illuminate\Support\Collection;

class VoteStatisticsCalculator
{
    /**
     * Calculate vote statistics from a collection of votes.
     *
     * @param  Collection<int, Vote>  $votes
     * @return array<string, mixed>
     */
    public function calculate(Collection $votes): array
    {
        $totalVotes = $votes->count();

        if ($totalVotes === 0) {
            return [
                'total_votes' => 0,
                'completed_votes' => 0,
                'vote_matches' => 0,
                'vote_mismatches' => 0,
                'incomplete_vote_data' => 0,
                'student_success_rate' => 0,
                'positive_outcomes' => 0,
                'negative_outcomes' => 0,
                'neutral_outcomes' => 0,
                'has_any_student_vote' => false,
                'has_any_decision' => false,
                'has_any_student_benefit' => false,
                'all_votes_complete' => false,
                'has_consensus_votes' => false,
                'consensus_votes_count' => 0,
            ];
        }

        $completedVotes = $votes->filter(function ($vote) {
            return ! empty($vote->student_vote)
                && ! empty($vote->decision)
                && ! empty($vote->student_benefit);
        })->count();

        $votesWithBoth = $votes->filter(function ($vote) {
            return ! empty($vote->student_vote) && ! empty($vote->decision);
        });

        $voteMatches = $votesWithBoth->filter(function ($vote) {
            return $vote->student_vote === $vote->decision;
        })->count();

        $voteMismatches = $votesWithBoth->count() - $voteMatches;

        $incompleteVoteData = $votes->filter(function ($vote) {
            $hasStudentVote = ! empty($vote->student_vote);
            $hasDecision = ! empty($vote->decision);

            return $hasStudentVote xor $hasDecision;
        })->count();

        $positive = $votes->where('decision', 'positive')->count();
        $negative = $votes->where('decision', 'negative')->count();
        $neutral = $votes->where('decision', 'neutral')->count();

        $hasAnyStudentVote = $votes->contains(fn ($v) => ! empty($v->student_vote));
        $hasAnyDecision = $votes->contains(fn ($v) => ! empty($v->decision));
        $hasAnyStudentBenefit = $votes->contains(fn ($v) => ! empty($v->student_benefit));
        $allComplete = $votes->every(fn ($v) => $v->is_complete);
        $consensusVotes = $votes->filter(fn ($v) => $v->is_consensus);

        return [
            'total_votes' => $totalVotes,
            'completed_votes' => $completedVotes,
            'vote_matches' => $voteMatches,
            'vote_mismatches' => $voteMismatches,
            'incomplete_vote_data' => $incompleteVoteData,
            'student_success_rate' => $totalVotes > 0 ? round(($voteMatches / $totalVotes) * 100) : 0,
            'positive_outcomes' => $positive,
            'negative_outcomes' => $negative,
            'neutral_outcomes' => $neutral,
            'has_any_student_vote' => $hasAnyStudentVote,
            'has_any_decision' => $hasAnyDecision,
            'has_any_student_benefit' => $hasAnyStudentBenefit,
            'all_votes_complete' => $allComplete,
            'has_consensus_votes' => $consensusVotes->isNotEmpty(),
            'consensus_votes_count' => $consensusVotes->count(),
        ];
    }

    /**
     * Calculate vote alignment status from a collection of votes.
     *
     * @param  Collection<int, Vote>  $votes
     * @return string 'all_match'|'mixed'|'all_mismatch'|'neutral'|'match'|'mismatch'|'incomplete'
     */
    public function alignmentStatus(Collection $votes): string
    {
        if ($votes->isEmpty()) {
            return 'neutral';
        }

        // Prefer main vote status if available
        $mainVote = $votes->firstWhere('is_main', true);
        if ($mainVote) {
            return $mainVote->vote_alignment_status;
        }

        $stats = $this->calculate($votes);

        if ($stats['vote_matches'] === 0 && $stats['vote_mismatches'] === 0) {
            return 'incomplete';
        }

        if ($stats['vote_mismatches'] === 0) {
            return 'match';
        }

        if ($stats['vote_matches'] === 0) {
            return 'mismatch';
        }

        return 'mixed';
    }

    /**
     * Calculate vote alignment status from pre-computed statistics.
     * Used when only the counts are available, not the full vote collection.
     *
     * @return string 'all_match'|'mixed'|'all_mismatch'|'neutral'
     */
    public function alignmentStatusFromCounts(int $matches, int $mismatches): string
    {
        $total = $matches + $mismatches;

        if ($total === 0) {
            return 'neutral';
        }

        if ($mismatches === 0) {
            return 'all_match';
        }

        if ($matches === 0) {
            return 'all_mismatch';
        }

        return 'mixed';
    }
}
