<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Goal> $goals
 * @method static \Database\Factories\GoalGroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalGroup onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalGroup withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalGroup withoutTrashed()
 * @mixin \Eloquent
 */
class GoalGroup extends Model
{
    use HasFactory, HasRelationships, HasUlids, LogsActivity, SoftDeletes;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }
}
