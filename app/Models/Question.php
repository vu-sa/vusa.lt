<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Question extends Model
{
    use HasFactory, LogsActivity, HasRelationships;

    protected $with = ['question_group'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty();
    }

    public function institution()
    {
        return $this->belongsTo(DutyInstitution::class);
    }

    public function doings()
    {
        return $this->belongsToMany(Doing::class);
    }

    public function question_group()
    {
        return $this->belongsTo(QuestionGroup::class);
    }

    public function users()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new DutyInstitution)->duties(), (new Duty())->users());
    }
}
