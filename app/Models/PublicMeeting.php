<?php

namespace App\Models;

use App\Enums\MeetingType;
use App\Models\Pivots\AgendaItem;
use App\Settings\MeetingSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Activity;

/**
 * PublicMeeting - Extends Meeting for public display and search indexing
 *
 * This model filters meetings for public visibility based on institution types
 * configured in MeetingSettings. Only meetings from configured institution types
 * are indexed and searchable.
 *
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property MeetingType|null $type
 * @property Carbon $start_time
 * @property string|null $end_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, AgendaItem> $agendaItems
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Model|\Eloquent $commentable
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read Collection<int, SharepointFile> $files
 * @property-read string $completion_status
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read bool $is_public
 * @property-read string|null $type_label
 * @property-read string|null $type_slug
 * @property-read Collection<int, Institution> $institutions
 * @property-read Collection<int, Task> $tasks
 * @property-read Collection<int, Tenant> $tenants
 * @property-read Collection<int, Type> $types
 * @property-read Collection<int, User> $users
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicMeeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicMeeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicMeeting onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicMeeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicMeeting withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicMeeting withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PublicMeeting extends Meeting
{
    use Searchable;

    /**
     * The table associated with the model.
     * Uses parent Meeting table since PublicMeeting is a filtered view.
     *
     * @var string
     */
    protected $table = 'meetings';

    /**
     * Override institutions relationship to use correct pivot table
     * Laravel would default to 'institution_public_meeting' based on model name
     */
    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_meeting', 'meeting_id', 'institution_id');
    }

    /**
     * Override types relationship to use correct morph name
     * Laravel would default to 'public_meeting' based on model name
     */
    public function types(): MorphToMany
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    /**
     * Override agendaItems relationship to use correct foreign key
     * Laravel would default to 'public_meeting_id' based on model name
     */
    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class, 'meeting_id', 'id');
    }

    /**
     * Determine if meeting should be indexed for public search
     */
    public function shouldBeSearchable(): bool
    {
        // Load institutions if not already loaded
        if (! $this->relationLoaded('institutions')) {
            $this->load('institutions.types');
        }

        // Check if any institution supports public meetings
        foreach ($this->institutions as $institution) {
            if ($institution->has_public_meetings) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get searchable array for Typesense indexing
     */
    public function toSearchableArray(): array
    {
        // Load required relationships
        $this->loadMissing([
            'institutions.types',
            'institutions.tenant',
            'agendaItems.votes',
            'types',
        ]);

        // Aggregate vote statistics from agenda items' votes
        $voteStats = $this->calculateVoteStatistics();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_time' => $this->start_time->timestamp,
            'start_time_formatted' => $this->start_time->format('Y-m-d H:i'),
            'year' => $this->start_time->year,
            'month' => $this->start_time->month,

            // Institution info (first institution for primary display)
            'institution_id' => $this->institutions->first()?->id,
            'institution_name_lt' => $this->institutions->first()?->getTranslation('name', 'lt'),
            'institution_name_en' => $this->institutions->first()?->getTranslation('name', 'en'),
            'tenant_shortname' => $this->institutions->first()?->tenant?->shortname,

            // Institution type (for faceting)
            'institution_type_id' => $this->institutions->first()?->types->first()?->id,
            'institution_type_title' => $this->institutions->first()?->types->first()?->title,

            // Agenda items count (basic info)
            'agenda_items_count' => $this->agendaItems->count(),

            // Total votes count
            'votes_count' => $voteStats['total_votes'],

            // Vote alignment (whether student position was accepted) - for public display
            'vote_matches' => $voteStats['vote_matches'],
            'vote_mismatches' => $voteStats['vote_mismatches'],
            'incomplete_vote_data' => $voteStats['incomplete_vote_data'],
            'vote_alignment_status' => $this->calculateVoteAlignmentStatus($voteStats),

            // For filtering
            'is_recent' => $this->start_time->isAfter(now()->subMonths(6)),

            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Calculate vote statistics from agenda items' votes.
     * Aggregates statistics from all votes across all agenda items.
     */
    protected function calculateVoteStatistics(): array
    {
        // Collect all votes from all agenda items
        $allVotes = $this->agendaItems->flatMap(fn ($item) => $item->votes);

        $totalVotes = $allVotes->count();

        // Votes with all three fields filled
        $completedVotes = $allVotes->filter(function ($vote) {
            return ! empty($vote->student_vote)
                && ! empty($vote->decision)
                && ! empty($vote->student_benefit);
        })->count();

        // Votes with both student_vote and decision filled
        $votesWithBoth = $allVotes->filter(function ($vote) {
            return ! empty($vote->student_vote) && ! empty($vote->decision);
        });

        // Vote matches (student position accepted)
        $voteMatches = $votesWithBoth->filter(function ($vote) {
            return $vote->student_vote === $vote->decision;
        })->count();

        // Vote mismatches (student position not accepted)
        $voteMismatches = $votesWithBoth->count() - $voteMatches;

        // Votes with incomplete data (only one of student_vote/decision filled)
        $incompleteVoteData = $allVotes->filter(function ($vote) {
            $hasStudentVote = ! empty($vote->student_vote);
            $hasDecision = ! empty($vote->decision);

            return $hasStudentVote xor $hasDecision;
        })->count();

        // Count outcomes by type (from decision field)
        $positive = $allVotes->where('decision', 'positive')->count();
        $negative = $allVotes->where('decision', 'negative')->count();
        $neutral = $allVotes->where('decision', 'neutral')->count();

        return [
            'total_votes' => $totalVotes,
            'completed_votes' => $completedVotes,
            'student_success_rate' => $totalVotes > 0 ? round(($voteMatches / $totalVotes) * 100) : 0,
            'positive_outcomes' => $positive,
            'negative_outcomes' => $negative,
            'neutral_outcomes' => $neutral,
            'vote_matches' => $voteMatches,
            'vote_mismatches' => $voteMismatches,
            'incomplete_vote_data' => $incompleteVoteData,
        ];
    }

    /**
     * Calculate vote alignment status for colored dot display
     *
     * @return string 'all_match' (green), 'mixed' (amber), 'all_mismatch' (red), 'neutral' (grey)
     */
    protected function calculateVoteAlignmentStatus(array $voteStats): string
    {
        $matches = $voteStats['vote_matches'];
        $mismatches = $voteStats['vote_mismatches'];
        $total = $matches + $mismatches;

        // No vote data available
        if ($total === 0) {
            return 'neutral';
        }

        // All matches (green)
        if ($mismatches === 0) {
            return 'all_match';
        }

        // All mismatches (red)
        if ($matches === 0) {
            return 'all_mismatch';
        }

        // Mixed (amber)
        return 'mixed';
    }

    /**
     * Get the index name for the model
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'public_meetings';
    }

    /**
     * Get the engine used to index the model
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('typesense');
    }
}
