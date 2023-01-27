<?php

namespace App\Models;

use App\Models\Traits\HasComments;
use App\Models\Comment;
use App\Models\Pivots\Doable;
use App\Models\Traits\HasDecisions;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
use App\States\Doing\DoingState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Doing extends Model 
{
    use HasFactory, HasComments, HasDecisions, HasRelationships, HasSharepointFiles, HasStates, HasTasks, HasUlids, LogsActivity, SoftDeletes;

    protected $with = ['types'];

    protected $guarded = [];

    protected $casts = [
        'state' => DoingState::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function doables()
    {
        return $this->hasMany(Doable::class);
    }

    public function goals()
    {
        return $this->morphedByMany(Goal::class, 'doable', 'doables');
    }

    public function matters()
    {
        return $this->morphedByMany(Matter::class, 'doable', 'doables');
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->padaliniai());
    }
}
