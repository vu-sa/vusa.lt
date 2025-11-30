<?php

namespace App\Models;

use App\Contracts\SharepointFileableContract;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\AgendaItem;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AgendaItem> $agendaItems
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SharepointFile> $files
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Institution> $institutions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Type> $types
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
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

    protected $casts = [
        'start_time' => 'datetime',
    ];

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
        if (!$this->relationLoaded('agendaItems')) {
            $this->load('agendaItems');
        }

        $agendaItems = $this->agendaItems;

        // No agenda items = no_items status
        if ($agendaItems->isEmpty()) {
            return 'no_items';
        }

        // Check if all agenda items have the 3 required fields filled
        $allComplete = $agendaItems->every(function ($item) {
            return !empty($item->student_vote)
                && !empty($item->decision)
                && !empty($item->student_benefit);
        });

        return $allComplete ? 'complete' : 'incomplete';
    }

    protected static function booted()
    {
        static::saved(function (Meeting $meeting) {
            // check if institution name $institution->getChanges()['name'] has changed
            if (array_key_exists('start_time', $meeting->getChanges())) {
                // dispatch event FileableNameUpdated
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
