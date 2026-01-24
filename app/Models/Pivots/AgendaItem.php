<?php

namespace App\Models\Pivots;

use App\Enums\AgendaItemType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Vote;
use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $meeting_id
 * @property string|null $matter_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $title
 * @property int $order
 * @property bool $brought_by_students
 * @property AgendaItemType|null $type
 * @property string|null $student_position
 * @property string|null $description
 * @property string|null $start_time
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Vote> $additionalVotes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Institution> $institutions
 * @property-read Vote|null $mainVote
 * @property-read Meeting $meeting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Vote> $votes
 *
 * @method static \Database\Factories\AgendaItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItem query()
 *
 * @mixin \Eloquent
 */
class AgendaItem extends Pivot
{
    use HasFactory, HasRelationships, HasUlids, LogsActivity, Searchable;

    protected $table = 'agenda_items';

    protected $touches = ['meeting'];

    public $incrementing = true;

    protected static function newFactory(): Factory
    {
        return AgendaItemFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => AgendaItemType::class,
            'brought_by_students' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function meeting(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get all votes for this agenda item.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'agenda_item_id', 'id')->orderBy('order');
    }

    /**
     * Get the main vote for this agenda item.
     */
    public function mainVote(): HasOne
    {
        return $this->hasOne(Vote::class, 'agenda_item_id', 'id')->where('is_main', true);
    }

    /**
     * Get additional (non-main) votes for this agenda item.
     */
    public function additionalVotes(): HasMany
    {
        return $this->hasMany(Vote::class, 'agenda_item_id', 'id')->where('is_main', false)->orderBy('order');
    }

    public function institutions()
    {
        return $this->hasManyDeepFromRelations($this->meeting(), (new Meeting)->institutions());
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->tenant());
    }

    /**
     * Get the index name for the model.
     * Uses 'agenda_items' for admin search (with scoped API keys for tenant filtering).
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'agenda_items';
    }

    /**
     * Get searchable array for Typesense indexing.
     * Includes tenant_ids for scoped API key filtering in admin search.
     */
    public function toSearchableArray(): array
    {
        // Load required relationships
        $this->loadMissing([
            'meeting.institutions.tenant',
            'votes',
        ]);

        $meeting = $this->meeting;

        // Handle orphaned agenda items (meeting was deleted)
        if ($meeting === null) {
            return [];
        }

        // Get tenant IDs for filtering with scoped API keys
        $tenantIds = $meeting->institutions
            ->pluck('tenant.id')
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        // Get tenant shortnames for faceting/display
        $tenantShortnames = $meeting->institutions
            ->pluck('tenant.shortname')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Get institution info
        $institution = $meeting->institutions->first();

        // Get main vote for backward-compatible fields
        $mainVote = $this->votes->firstWhere('is_main', true);

        // Calculate vote statistics from all votes
        $voteStats = $this->calculateVoteStatistics();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,

            // New fields
            'type' => $this->type?->value ?? 'voting',
            'student_position' => $this->student_position,
            'brought_by_students' => (bool) $this->brought_by_students,

            // Main vote fields (for backward compatibility in search)
            'student_vote' => $mainVote?->student_vote,
            'decision' => $mainVote?->decision,
            'student_benefit' => $mainVote?->student_benefit,

            // Vote counts
            'votes_count' => $this->votes->count(),
            'main_vote_exists' => $mainVote !== null,

            // Tenant filtering (CRITICAL for scoped API keys)
            'tenant_ids' => $tenantIds,
            'tenant_shortnames' => $tenantShortnames,

            // Meeting info for context
            'meeting_id' => $this->meeting_id,
            'meeting_title' => $meeting->title,
            'meeting_start_time' => $meeting->start_time->timestamp,
            'meeting_start_time_formatted' => $meeting->start_time->format('Y-m-d H:i'),
            'meeting_year' => $meeting->start_time->year,
            'meeting_month' => $meeting->start_time->month,

            // Institution info
            'institution_id' => $institution?->id,
            'institution_name_lt' => $institution?->getTranslation('name', 'lt'),
            'institution_name_en' => $institution?->getTranslation('name', 'en'),

            // All institutions (for .own scope filtering)
            'institution_ids' => $meeting->institutions->pluck('id')->toArray(),

            // Completion status indicators (based on all votes)
            'has_student_vote' => $voteStats['has_any_student_vote'],
            'has_decision' => $voteStats['has_any_decision'],
            'has_student_benefit' => $voteStats['has_any_student_benefit'],
            'is_complete' => $voteStats['all_votes_complete'],

            // Vote alignment (based on all votes) - boolean for Typesense compatibility
            'vote_matches' => $voteStats['vote_matches'] > 0,
            'vote_mismatches' => $voteStats['vote_mismatches'] > 0,
            'vote_alignment_status' => $this->calculateVoteAlignmentStatus(),

            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }

    /**
     * Calculate vote statistics from all votes for this agenda item.
     */
    protected function calculateVoteStatistics(): array
    {
        $votes = $this->votes;

        if ($votes->isEmpty()) {
            return [
                'has_any_student_vote' => false,
                'has_any_decision' => false,
                'has_any_student_benefit' => false,
                'all_votes_complete' => false,
                'vote_matches' => 0,
                'vote_mismatches' => 0,
            ];
        }

        $hasAnyStudentVote = $votes->contains(fn ($v) => ! empty($v->student_vote));
        $hasAnyDecision = $votes->contains(fn ($v) => ! empty($v->decision));
        $hasAnyStudentBenefit = $votes->contains(fn ($v) => ! empty($v->student_benefit));
        $allComplete = $votes->every(fn ($v) => $v->is_complete);

        // Count vote alignment
        $votesWithBoth = $votes->filter(fn ($v) => ! empty($v->student_vote) && ! empty($v->decision));
        $voteMatches = $votesWithBoth->filter(fn ($v) => $v->student_vote === $v->decision)->count();
        $voteMismatches = $votesWithBoth->count() - $voteMatches;

        return [
            'has_any_student_vote' => $hasAnyStudentVote,
            'has_any_decision' => $hasAnyDecision,
            'has_any_student_benefit' => $hasAnyStudentBenefit,
            'all_votes_complete' => $allComplete,
            'vote_matches' => $voteMatches,
            'vote_mismatches' => $voteMismatches,
        ];
    }

    /**
     * Calculate overall vote alignment status for this agenda item.
     * Based on main vote if available, otherwise aggregated from all votes.
     *
     * @return string 'match', 'mismatch', 'mixed', 'incomplete', 'neutral'
     */
    protected function calculateVoteAlignmentStatus(): string
    {
        $votes = $this->relationLoaded('votes') ? $this->votes : $this->votes()->get();

        if ($votes->isEmpty()) {
            return 'neutral';
        }

        // Prefer main vote status if available
        $mainVote = $votes->firstWhere('is_main', true);
        if ($mainVote) {
            return $mainVote->vote_alignment_status;
        }

        // Otherwise aggregate from all votes
        $stats = $this->calculateVoteStatistics();

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
     * Get the engine used to index the model
     */
    public function searchableUsing()
    {
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
    }
}
