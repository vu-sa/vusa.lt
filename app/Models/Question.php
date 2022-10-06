<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Question extends Model
{
    use HasFactory, LogsActivity;

    protected $with = ['question_group'];

    protected $guarded = [];

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }
}
