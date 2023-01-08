<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Goal extends Model
{
    use HasFactory, HasUlids, SoftDeletes, LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function matters()
    {
        return $this->belongsToMany(InstitutionMatter::class, 'goal_institution_matter', 'goal_id', 'matter_id');
    }

    public function doings()
    {
        return $this->morphToMany(Doing::class, 'doables')->using(Doable::class)->withTimestamps();
    }

    public function group()
    {
        return $this->belongsTo(GoalGroup::class);
    }
}
