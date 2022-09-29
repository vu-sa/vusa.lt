<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Doing extends Model
{
    use HasFactory, LogsActivity;

    protected $with = ['doing_type'];

    protected $guarded = [];

    public function question()
    {
        return $this->belongsTo(DutyInstitution::class);
    }

    public function doing_type()
    {
        return $this->belongsTo(DoingType::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }
}
