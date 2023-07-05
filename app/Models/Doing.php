<?php

namespace App\Models;

use App\Models\Interfaces\Decidable;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
use App\Models\Traits\MakesDecisions;
use App\States\Doing\DoingState;
use App\States\Doing\PendingFinalApproval;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Illuminate\Auth\Access\AuthorizationException;

class Doing extends Model implements Decidable
{
    use HasFactory, HasStates, HasComments, MakesDecisions, HasRelationships, HasSharepointFiles, HasTasks, HasUlids, LogsActivity, SoftDeletes;

    protected $with = ['types'];

    protected $guarded = [];

    protected $casts = [
        'state' => DoingState::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    // public function doables()
    // {
    //     return $this->hasMany(Doable::class);
    // }

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

    public function institutions()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->institutions());
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->padaliniai());
    }

    // ! About decisions:
    // for some decisions, e.g. progress, we need to check against the model
    // for some decisions, e.g. approve, we need to check against the role
    // That's why sometimes we use $this->authorize() and sometimes we use $this->authorizer
    // TODO: it returns inconsistently, maybe needs fixing

    public function decisionToProgress()
    {
        $this->authorize('update', [$this::class, $this, $this->authorizer]);

        return $this->state->handleProgress();
    }

    public function decisionToApprove()
    {
        if(!$this->authorizer->forUser(auth()->user())->check($this->modelName.'.update.padalinys')) {
            // throw authorization exception if user is not authorized
            abort('Neturite teisių patvirtinti veiklai.', 403);
        }

        if ($this->state instanceof PendingFinalApproval) {
            abort_if($this->authorizer->isAllScope === false, 403, 'Neturite pakankamų teisių patvirtinti arba atmesti.');
        }

        return $this->state->handleApprove();
    }

    public function decisionToReject()
    {
        if(!$this->authorizer->forUser(auth()->user())->check($this->modelName.'.update.padalinys')) {
            // throw authorization exception if user is not authorized
            abort('Neturite teisių atmesti veiklai.', 403);
        }

        if ($this->state instanceof PendingFinalApproval) {
            abort_if($this->authorizer->isAllScope === false, 403, 'Neturite pakankamų teisių patvirtinti arba atmesti.');
        }

        return $this->state->handleReject();
    }

    public function decisionToCancel()
    {
        $this->authorize('update', [$this::class, $this, $this->authorizer]);

        return $this->state->handleCancel();
    }
}
