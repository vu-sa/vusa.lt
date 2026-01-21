<?php

namespace App\Models\Pivots;

use App\Models\Institution;
use App\Models\Meeting;
use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property int $brought_by_students
 * @property string|null $description
 * @property string|null $student_vote
 * @property string|null $decision
 * @property string|null $student_benefit
 * @property string|null $start_time
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Institution> $institutions
 * @property-read Meeting $meeting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function meeting(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Meeting::class);
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
        ]);

        $meeting = $this->meeting;

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

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,

            // Decision fields (searchable content)
            'student_vote' => $this->student_vote,
            'decision' => $this->decision,
            'student_benefit' => $this->student_benefit,
            'brought_by_students' => (bool) $this->brought_by_students,

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
            // Institution IDs are ULIDs (strings)
            'institution_ids' => $meeting->institutions->pluck('id')->toArray(),

            // Completion status indicators
            'has_student_vote' => ! empty($this->student_vote),
            'has_decision' => ! empty($this->decision),
            'has_student_benefit' => ! empty($this->student_benefit),
            'is_complete' => ! empty($this->student_vote) && ! empty($this->decision) && ! empty($this->student_benefit),

            // Vote alignment
            'vote_matches' => ! empty($this->student_vote) && ! empty($this->decision) && $this->student_vote === $this->decision,
            'vote_alignment_status' => $this->calculateVoteAlignmentStatus(),

            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }

    /**
     * Calculate vote alignment status for this agenda item
     *
     * @return string 'match', 'mismatch', 'incomplete', 'neutral'
     */
    protected function calculateVoteAlignmentStatus(): string
    {
        $hasStudentVote = ! empty($this->student_vote);
        $hasDecision = ! empty($this->decision);

        // Neither vote nor decision recorded
        if (! $hasStudentVote && ! $hasDecision) {
            return 'neutral';
        }

        // Only one is filled - incomplete data
        if ($hasStudentVote ^ $hasDecision) {
            return 'incomplete';
        }

        // Both filled - check if they match
        return $this->student_vote === $this->decision ? 'match' : 'mismatch';
    }

    /**
     * Get the engine used to index the model
     */
    public function searchableUsing()
    {
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
    }
}
