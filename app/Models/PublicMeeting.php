<?php

namespace App\Models;

use App\Enums\MeetingType;
use App\Models\Pivots\AgendaItem;
use App\Services\VoteStatisticsCalculator;
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
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read string $completion_status
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read bool $is_joint
 * @property-read bool $is_public
 * @property-read string|null $type_label
 * @property-read string|null $type_slug
 * @property-read Collection<int, Institution> $institutions
 * @property-read Collection<int, Comment> $rootComments
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
     *
     * @return HasMany<AgendaItem, $this>
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
     * Delegates to VoteStatisticsCalculator.
     */
    protected function calculateVoteStatistics(): array
    {
        $allVotes = $this->agendaItems->flatMap(fn ($item) => $item->votes);

        return app(VoteStatisticsCalculator::class)->calculate($allVotes);
    }

    /**
     * Calculate vote alignment status for colored dot display.
     * Delegates to VoteStatisticsCalculator.
     *
     * @return string 'all_match' (green), 'mixed' (amber), 'all_mismatch' (red), 'neutral' (grey)
     */
    protected function calculateVoteAlignmentStatus(array $voteStats): string
    {
        return app(VoteStatisticsCalculator::class)->alignmentStatusFromCounts(
            $voteStats['vote_matches'],
            $voteStats['vote_mismatches']
        );
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
