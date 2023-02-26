<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

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

    public function doings()
    {
        return $this->hasManyDeepFromRelations($this->matters(), (new Goal()))->doings();
    }
}
