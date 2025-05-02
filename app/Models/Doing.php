<?php

namespace App\Models;

use App\Models\Interfaces\Decidable;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
use App\Models\Traits\MakesDecisions;
use App\Services\ModelAuthorizer;
use App\States\Doing\DoingState;
use App\States\Doing\PendingFinalApproval;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Doing extends Model implements Decidable
{
    use HasComments, HasFactory, HasRelationships, HasSharepointFiles, HasStates, HasTasks, HasUlids, LogsActivity, MakesDecisions, Searchable, SoftDeletes;

    protected $with = ['types'];

    protected $guarded = [];

    protected $casts = [
        'state' => DoingState::class,
    ];

    /**
     * Authorize an action against the model
     *
     * @param string $ability The ability to check
     * @param array $arguments Additional arguments
     * @return bool
     */
    protected function authorize(string $ability, array $arguments = []): bool
    {
        // If authorizer is explicitly passed, use it
        $authorizer = $arguments[2] ?? app(ModelAuthorizer::class);
        
        // Check authorization for this model and ability
        if (!$authorizer->forUser(auth()->user())->check($this->modelName.'.'.$ability.'.own')) {
            abort(403, 'Neturite teisių atlikti šį veiksmą.');
        }
        
        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
        ];
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

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->tenants());
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
        if (! $this->authorizer->forUser(auth()->user())->check($this->modelName.'.update.padalinys')) {
            // throw authorization exception if user is not authorized
            abort(403, 'Neturite teisių patvirtinti veiklai.');
        }

        if ($this->state instanceof PendingFinalApproval) {
            abort_if($this->authorizer->isAllScope === false, 403, 'Neturite pakankamų teisių patvirtinti arba atmesti.');
        }

        return $this->state->handleApprove();
    }

    public function decisionToReject()
    {
        if (! $this->authorizer->forUser(auth()->user())->check($this->modelName.'.update.padalinys')) {
            // throw authorization exception if user is not authorized
            abort(403, 'Neturite teisių atmesti veiklai.');
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
