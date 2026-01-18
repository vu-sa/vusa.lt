<?php

namespace App\Models;

use App\Contracts\SharepointFileableContract;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\AgendaItem;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $start_time
 * @property string|null $end_time
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read Collection<int, AgendaItem> $agendaItems
 * @property-read Collection<int, \App\Models\FileableFile> $availableFiles
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read Collection<int, \App\Models\Comment> $comments
 * @property-read Collection<int, \App\Models\FileableFile> $fileableFiles
 * @property-read Collection<int, \App\Models\SharepointFile> $files
 * @property-read string $completion_status
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read bool $is_public
 * @property-read Collection<int, \App\Models\Institution> $institutions
 * @property-read Collection<int, \App\Models\Task> $tasks
 * @property-read Collection<int, \App\Models\Tenant> $tenants
 * @property-read Collection<int, \App\Models\Type> $types
 * @property-read Collection<int, \App\Models\User> $users
 *
 * @method static \Database\Factories\MeetingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Meeting extends Model implements SharepointFileableContract
{
    use HasComments, HasFactory, HasRelationships, HasSharepointFiles, HasTasks, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
        ];
    }

    // Note: is_public is NOT auto-appended due to performance (triggers N+1 queries).
    // Append it explicitly where needed: $meeting->append('is_public')

    /**
     * Check if meeting is publicly visible based on institution types.
     * Uses Institution::has_public_meetings which checks MeetingSettings.
     */
    public function getIsPublicAttribute(): bool
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
     * Get the index name for the model.
     * Uses 'meetings' for admin search (with scoped API keys for tenant filtering).
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'meetings';
    }

    /**
     * Get the engine used to index the model
     */
    public function searchableUsing()
    {
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
    }

    /**
     * Get searchable array for Typesense indexing.
     * Includes tenant_ids for scoped API key filtering in admin search.
     */
    public function toSearchableArray(): array
    {
        // Load required relationships
        $this->loadMissing([
            'institutions.types',
            'institutions.tenant',
            'agendaItems',
            'types',
        ]);

        // Get tenant IDs for filtering with scoped API keys
        $tenantIds = $this->institutions
            ->pluck('tenant.id')
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        // Get tenant shortnames for faceting/display
        $tenantShortnames = $this->institutions
            ->pluck('tenant.shortname')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Aggregate vote statistics from agenda items
        $voteStats = $this->calculateVoteStatistics();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_time' => $this->start_time->timestamp,
            'start_time_formatted' => $this->start_time->format('Y-m-d H:i'),
            'year' => $this->start_time->year,
            'month' => $this->start_time->month,

            // Tenant filtering (CRITICAL for scoped API keys)
            'tenant_ids' => $tenantIds,
            'tenant_shortnames' => $tenantShortnames,

            // Institution info (first institution for primary display)
            'institution_id' => $this->institutions->first()?->id,
            'institution_name_lt' => $this->institutions->first()?->getTranslation('name', 'lt'),
            'institution_name_en' => $this->institutions->first()?->getTranslation('name', 'en'),

            // All institutions (for multi-institution meetings and .own scope filtering)
            // Institution IDs are ULIDs (strings)
            'institution_ids' => $this->institutions->pluck('id')->toArray(),
            'institution_names' => $this->institutions->map(fn ($i) => $i->getTranslation('name', 'lt'))->toArray(),

            // Institution type (for faceting)
            'institution_type_id' => $this->institutions->first()?->types->first()?->id,
            'institution_type_title' => $this->institutions->first()?->types->first()?->title,

            // Agenda items count
            'agenda_items_count' => $this->agendaItems->count(),

            // Vote alignment statistics
            'vote_matches' => $voteStats['vote_matches'],
            'vote_mismatches' => $voteStats['vote_mismatches'],
            'incomplete_vote_data' => $voteStats['incomplete_vote_data'],
            'vote_alignment_status' => $this->calculateVoteAlignmentStatus($voteStats),

            // Completion status for filtering
            'completion_status' => $this->completion_status,

            // Visibility status
            'is_public' => $this->is_public,
            'is_recent' => $this->start_time->isAfter(now()->subMonths(6)),

            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }

    /**
     * Calculate vote statistics from agenda items
     */
    protected function calculateVoteStatistics(): array
    {
        $items = $this->agendaItems;

        // Count vote alignment - items where both student_vote and decision exist
        $itemsWithBothVotes = $items->filter(function ($item) {
            return ! empty($item->student_vote) && ! empty($item->decision);
        });

        $voteMatches = $itemsWithBothVotes->filter(function ($item) {
            return $item->student_vote === $item->decision;
        })->count();

        $voteMismatches = $itemsWithBothVotes->count() - $voteMatches;

        // Items with incomplete vote data
        $incompleteVoteData = $items->filter(function ($item) {
            $hasStudentVote = ! empty($item->student_vote);
            $hasDecision = ! empty($item->decision);

            return $hasStudentVote ^ $hasDecision;
        })->count();

        return [
            'vote_matches' => $voteMatches,
            'vote_mismatches' => $voteMismatches,
            'incomplete_vote_data' => $incompleteVoteData,
        ];
    }

    /**
     * Calculate vote alignment status for filtering
     *
     * @return string 'all_match', 'mixed', 'all_mismatch', 'neutral'
     */
    protected function calculateVoteAlignmentStatus(array $voteStats): string
    {
        $matches = $voteStats['vote_matches'];
        $mismatches = $voteStats['vote_mismatches'];
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function agendaItems()
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function users()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->users());
    }

    /**
     * Get student representatives who were active at the time of this meeting.
     * Filters duties by 'studentu-atstovai' type and checks dutiables pivot dates.
     *
     * @return Collection<User>
     */
    public function getRepresentativesActiveAt(): Collection
    {
        $meetingDate = $this->start_time->toDateString();

        // Get all institution IDs for this meeting
        $institutionIds = $this->institutions->pluck('id');

        if ($institutionIds->isEmpty()) {
            return new Collection;
        }

        // Get the student representative type
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first();

        if (! $studentRepType) {
            return new Collection;
        }

        // Get duties that belong to these institutions and have the student rep type
        $dutyIds = Duty::query()
            ->whereIn('institution_id', $institutionIds)
            ->whereHas('types', fn ($q) => $q->where('types.id', $studentRepType->id))
            ->pluck('id');

        if ($dutyIds->isEmpty()) {
            return new Collection;
        }

        // Get users who were active in these duties at the meeting date
        return User::query()
            ->whereHas('duties', function ($query) use ($dutyIds, $meetingDate) {
                $query->whereIn('duties.id', $dutyIds)
                    ->where('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate) {
                        $q->whereNull('dutiables.end_date')
                            ->orWhere('dutiables.end_date', '>=', $meetingDate);
                    });
            })
            ->with(['duties' => function ($query) use ($dutyIds, $meetingDate) {
                // Also load the specific duty info for context
                $query->whereIn('duties.id', $dutyIds)
                    ->where('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate) {
                        $q->whereNull('dutiables.end_date')
                            ->orWhere('dutiables.end_date', '>=', $meetingDate);
                    });
            }])
            ->get()
            ->unique('id');
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->tenant());
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    /**
     * Check if all agenda items have completion fields filled.
     *
     * @return string 'complete'|'incomplete'|'no_items'
     */
    public function getCompletionStatusAttribute(): string
    {
        // Load agenda items if not already loaded
        if (! $this->relationLoaded('agendaItems')) {
            $this->load('agendaItems');
        }

        $agendaItems = $this->agendaItems;

        // No agenda items = no_items status
        if ($agendaItems->isEmpty()) {
            return 'no_items';
        }

        // Check if all agenda items have the 3 required fields filled
        $allComplete = $agendaItems->every(function ($item) {
            return ! empty($item->student_vote)
                && ! empty($item->decision)
                && ! empty($item->student_benefit);
        });

        return $allComplete ? 'complete' : 'incomplete';
    }

    protected static function booted()
    {
        static::saving(function (Meeting $meeting) {
            // Dispatch event when start_time is about to change - SharePoint must succeed first
            if ($meeting->isDirty('start_time')) {
                FileableNameUpdated::dispatch($meeting);
            }
        });
    }

    protected static function boot()
    {
        parent::boot();

        // When a meeting is deleted, also delete its agenda items
        static::deleting(function ($meeting) {
            $meeting->agendaItems()->delete();
        });
    }
}
