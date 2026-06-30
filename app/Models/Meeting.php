<?php

namespace App\Models;

use App\Contracts\SharepointFileableContract;
use App\Enums\MeetingType;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\AgendaItem;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
use App\Services\MeetingCompletionService;
use App\Services\MeetingRepresentativeResolver;
use App\Services\VoteStatisticsCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
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
 * @property-read Collection<int, User> $users
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
            'type' => MeetingType::class,
        ];
    }

    // Note: is_public is NOT auto-appended due to performance (triggers N+1 queries).
    // Append it explicitly where needed: $meeting->append('is_public')

    /**
     * Check if meeting involves multiple institutions (joint meeting / jungtinis posėdis).
     * Note: is_joint is NOT auto-appended due to performance (triggers N+1 queries).
     * Append it explicitly where needed: $meeting->append('is_joint')
     */
    public function getIsJointAttribute(): bool
    {
        if (! $this->relationLoaded('institutions')) {
            $this->load('institutions');
        }

        return $this->institutions->count() > 1;
    }

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
     * Calculate vote statistics from agenda items' votes.
     * Delegates to VoteStatisticsCalculator.
     */
    protected function calculateVoteStatistics(): array
    {
        $allVotes = $this->agendaItems->flatMap(fn ($item) => $item->votes);

        return app(VoteStatisticsCalculator::class)->calculate($allVotes);
    }

    /**
     * Calculate vote alignment status for filtering.
     * Delegates to VoteStatisticsCalculator.
     *
     * @return string 'all_match', 'mixed', 'all_mismatch', 'neutral'
     */
    protected function calculateVoteAlignmentStatus(array $voteStats): string
    {
        return app(VoteStatisticsCalculator::class)->alignmentStatusFromCounts(
            $voteStats['vote_matches'],
            $voteStats['vote_mismatches']
        );
    }

    /**
     * Get the engine used to index the model
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('typesense');
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
            'agendaItems.votes',
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

            // Total votes count
            'votes_count' => $voteStats['total_votes'],

            // Meeting type (enum)
            'type' => $this->type?->value,
            'type_slug' => $this->type_slug,
            'type_label_lt' => $this->type?->label('lt'),
            'type_label_en' => $this->type?->label('en'),

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    /** @return HasMany<AgendaItem, $this> */
    public function agendaItems(): HasMany
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
     * Delegates to MeetingRepresentativeResolver.
     *
     * @return Collection<int, User>
     */
    public function getRepresentativesActiveAt(): Collection
    {
        return app(MeetingRepresentativeResolver::class)->resolve($this);
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->tenant());
    }

    /**
     * Get the localized type label.
     */
    public function getTypeLabelAttribute(): ?string
    {
        return $this->type?->label(app()->getLocale());
    }

    /**
     * Get the type value (used for icon mapping etc).
     */
    public function getTypeSlugAttribute(): ?string
    {
        return $this->type?->value;
    }

    /**
     * Check if all agenda items have votes with completion fields filled.
     *
     * @return string 'complete'|'incomplete'|'no_items'
     */
    public function getCompletionStatusAttribute(): string
    {
        return app(MeetingCompletionService::class)->calculate($this);
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
