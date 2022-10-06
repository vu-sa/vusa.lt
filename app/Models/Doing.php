<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasComments;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Doing extends Model
{
    use HasFactory, LogsActivity, HasComments;

    protected $with = ['types'];

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function documents()
    {
        return $this->morphMany(SharepointDocument::class, 'documentable');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

}
