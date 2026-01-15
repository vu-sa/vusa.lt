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

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
        ];
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
