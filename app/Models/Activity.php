<?php

namespace App\Models;

use App\Services\ActivityRootResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Extends Spatie's Activity model to add the "root subject" roll-up: every
 * activity also points at the top of its model tree (e.g. a Vote's root is
 * its Meeting), so a single indexed query can return a whole tree's history.
 *
 * See App\Support\ActivityRoots for which models roll up to which parent.
 *
 * Registered as config('activitylog.activity_model') so Spatie's LogsActivity
 * trait creates instances of this class instead of the vendor default.
 *
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property string|null $event
 * @property string|null $subject_id
 * @property string|null $root_subject_type
 * @property string|null $root_subject_id
 * @property string|null $causer_type
 * @property string|null $causer_id
 * @property Collection<array-key, mixed>|null $attribute_changes
 * @property Collection<array-key, mixed>|null $properties
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $causer
 * @property-read Model|null $rootSubject
 * @property-read Model|null $subject
 *
 * @method static Builder<static>|Activity causedBy(\Illuminate\Database\Eloquent\Model $causer)
 * @method static Builder<static>|Activity forEvent(\Spatie\Activitylog\Enums\ActivityEvent|string $event)
 * @method static Builder<static>|Activity forRoot(string $type, string $id)
 * @method static Builder<static>|Activity forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static Builder<static>|Activity inLog(\BackedEnum|array|string ...$logNames)
 * @method static Builder<static>|Activity newModelQuery()
 * @method static Builder<static>|Activity newQuery()
 * @method static Builder<static>|Activity query()
 *
 * @mixin \Eloquent
 */
class Activity extends \Spatie\Activitylog\Models\Activity
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function rootSubject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<Activity>  $query
     */
    public function scopeForRoot(Builder $query, string $type, string $id): Builder
    {
        return $query->where('root_subject_type', $type)->where('root_subject_id', $id);
    }

    #[\Override]
    protected static function booted(): void
    {
        parent::booted();

        // Backstop for activities created outside the normal LogsActivity event
        // path (seeders, direct Activity::create(), tests) -- the primary
        // stamping point is the beforeLogging callback registered in
        // ActivityLogServiceProvider, which also covers manual activity() calls
        // and buffered logging (config('activitylog.buffer.enabled')), neither
        // of which fires this Eloquent event.
        static::creating(function (self $activity): void {
            if ($activity->root_subject_type === null && $activity->subject) {
                app(ActivityRootResolver::class)->stamp($activity);
            }
        });
    }
}
