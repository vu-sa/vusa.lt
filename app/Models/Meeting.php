<?php

namespace App\Models;

use App\Actions\Cadences\SyncCadenceDatesFromAnchors;
use App\Contracts\Commentable;
use App\Contracts\SharepointFileableContract;
use App\Enums\MeetingType;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\AgendaItem;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
use App\Models\Traits\LogsModelActivity;
use App\Models\Traits\LogsRelationshipChanges;
use App\Services\MeetingCompletionService;
use App\Services\MeetingRepresentativeResolver;
use App\Services\VoteStatisticsCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property MeetingType|null $type
 * @property Carbon $start_time
 * @property Carbon|null $end_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Collection<int, AgendaItem> $agendaItems
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read string $completion_status
 * @property-read bool $has_protocol
 * @property-read bool $has_calendar_event
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
class Meeting extends Model implements Commentable, SharepointFileableContract
{
    use HasComments, HasFactory, HasRelationships, HasSharepointFiles, HasTasks, HasUlids, LogsModelActivity, LogsRelationshipChanges, Searchable, SoftDeletes;

    #[\Override]
    protected $guarded = [];

    #[\Override]
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            // The column has always been a datetime; leaving it uncast made `end_time` a
            // string while `start_time` was a Carbon, which broke every caller that treated
            // the pair alike (AnnounceMeetingInCalendar, syncCalendarEventTiming).
            'end_time' => 'datetime',
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
        return $this->isPubliclyVisible();
    }

    /**
     * The single answer to "may the public see this meeting" — the meeting page, institution
     * meeting lists and the public search index all gate on this. Settings-only, deliberately:
     * a published calendar announcement still shows the agenda inline on the event page (see
     * PublicPageController::meetingBehind()), but does not by itself open the meeting page or
     * search entry — those stay behind MeetingSettings::public_meeting_institution_type_ids.
     */
    public function isPubliclyVisible(): bool
    {
        if (! $this->relationLoaded('institutions')) {
            $this->load('institutions.types');
        }

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

        return app(VoteStatisticsCalculator::class)->calculate($allVotes, $this->requiresStudentPerspective());
    }

    /**
     * Whether this meeting records how the students voted and whether the outcome favoured them.
     *
     * False only for VU SA's own bodies — there the representatives *are* the organisation, so
     * `student_vote` and `student_benefit` have no separate answer and demanding them left every
     * such meeting permanently "incomplete".
     */
    public function requiresStudentPerspective(): bool
    {
        $this->loadMissing('institutions.types');

        return app(MeetingCompletionService::class)
            ->institutionsRequireStudentPerspective($this->institutions);
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
            'users',
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

            // Which governance world the meeting belongs to (facet + vote-field vocabulary)
            'governance_scope' => $this->institutions->first()?->governance_scope->value,

            // Visibility status
            'is_public' => $this->is_public,
            'is_recent' => $this->start_time->isAfter(now()->subMonths(6)),

            // Representatives attending the meeting
            'user_names' => $this->users->pluck('name')->filter()->unique()->values()->all(),

            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
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

    /**
     * The public announcement of this meeting, when someone has made one.
     *
     * @return HasOne<Calendar, $this>
     */
    public function calendarEvent(): HasOne
    {
        return $this->hasOne(Calendar::class, 'meeting_id');
    }

    /**
     * Nutarimai, protokolai and other SharePoint documents produced by this meeting.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'meeting_id');
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

    #[\Override]
    protected static function booted()
    {
        static::saving(function (Meeting $meeting): void {
            // Dispatch event when start_time is about to change - SharePoint must succeed first
            if ($meeting->isDirty('start_time')) {
                FileableNameUpdated::dispatch($meeting);
            }
        });

        static::saved(function (Meeting $meeting): void {
            $meeting->syncCalendarEventTiming();
            $meeting->syncAnchoredCadences();
            $meeting->syncPublicSearchIndex();
        });

        static::deleted(function (Meeting $meeting): void {
            $meeting->publicSearchModel()->unsearchable();
        });

        static::forceDeleted(function (Meeting $meeting): void {
            $meeting->publicSearchModel()->unsearchable();
        });

        static::restored(function (Meeting $meeting): void {
            $publicMeeting = PublicMeeting::query()->find($meeting->getKey());

            if ($publicMeeting?->shouldBeSearchable()) {
                $publicMeeting->searchable();
            }
        });

        static::deleting(function (Meeting $meeting): void {
            // A soft delete has to stay reversible. Agenda items are not soft-deletable
            // and votes + agenda_item_notes CASCADE off them, so removing them here would
            // destroy the substance of the meeting — restore would return an empty shell.
            if (! $meeting->isForceDeleting()) {
                return;
            }

            // Iterated rather than mass-deleted: `agendaItems()->delete()` fires no model
            // events, so Scout never un-indexes the agenda items and they linger in the
            // search index pointing at rows that no longer exist.
            $meeting->agendaItems->each->delete();

            // institution_meeting.meeting_id restricts deletes and nothing else detaches
            // it, so without this permanent deletion fails for every meeting that has an
            // institution — which is essentially all of them.
            $meeting->institutions()->detach();
        });
    }

    /**
     * Re-evaluate this meeting's place in the public search index.
     *
     * Public here, not private: publishing a linked Calendar event changes the answer without
     * touching the meeting row, so Calendar's own hooks call this too.
     */
    public function syncPublicSearchIndex(): void
    {
        $publicMeeting = PublicMeeting::query()->find($this->getKey());

        if ($publicMeeting?->shouldBeSearchable()) {
            $publicMeeting->searchable();

            return;
        }

        $this->publicSearchModel()->unsearchable();
    }

    /**
     * Whether the meeting is announced in the public calendar at all.
     *
     * Appended rather than computed in the DTO because the dashboard's user section
     * serialises the relation straight through — see DashboardController::atstovavimas().
     */
    public function getHasCalendarEventAttribute(): bool
    {
        return $this->calendarEvent !== null;
    }

    /**
     * A term anchored to this sitting moves with it. Same direction of truth as the calendar
     * announcement: the meeting owns the date, the thing pointing at it follows.
     */
    private function syncAnchoredCadences(): void
    {
        if (! $this->wasChanged('start_time')) {
            return;
        }

        SyncCadenceDatesFromAnchors::forMeeting($this);
    }

    /**
     * The meeting owns its timing; the announcement follows it, never the other way round.
     */
    private function syncCalendarEventTiming(): void
    {
        if (! $this->wasChanged(['start_time', 'end_time'])) {
            return;
        }

        $event = $this->calendarEvent()->first();

        if ($event === null) {
            return;
        }

        // Saved through the model, not `$relation->update()`: a query-builder update fires no
        // model events, so Calendar's own `saved` hooks — the calendar/iCal cache flush and the
        // public search resync — would never run and the feeds would serve the old date.
        $event->date = $this->start_time;
        $event->end_date = $this->end_time;
        $event->save();
    }

    private function publicSearchModel(): PublicMeeting
    {
        $publicMeeting = new PublicMeeting;
        $publicMeeting->setAttribute($publicMeeting->getKeyName(), $this->getKey());

        return $publicMeeting;
    }
}
