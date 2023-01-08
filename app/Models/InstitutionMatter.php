<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class InstitutionMatter extends Model
{
    use HasFactory, HasRelationships, HasUlids, LogsActivity, SoftDeletes;

    protected $casts = [
        'created_at' => 'timestamp',
    ];

    protected $guarded = [];

    protected $table = 'matters';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function institutions()
    {
        return $this->belongsToMany(Institution::class, 'institutions_matters', 'matter_id', 'institution_id');
    }

    public function meetings()
    {
        return $this->belongsToMany(InstitutionMeeting::class, 'institution_meeting_matter', 'matter_id', 'meeting_id');
    }

    public function doings()
    {
        return $this->morphToMany(Doing::class, 'doable', 'doables')->using(Doable::class)->withTimestamps();
    }

    public function goals()
    {
        return $this->belongsToMany(Goal::class, 'goal_institution_matter', 'matter_id', 'goal_id');
    }

    public function users()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution())->duties(), (new Duty())->users());
    }
}
