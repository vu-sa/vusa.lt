<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InstitutionMeeting extends Model
{
    use HasFactory, HasUlids, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'start_time' => 'timestamp',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function matters()
    {
        return $this->belongsToMany(InstitutionMatter::class, 'institution_meeting_matter', 'meeting_id', 'matter_id')->using(InstitutionMeetingMatter::class);
    }

    public function documents()
    {
        return $this->morphMany(SharepointDocument::class, 'documentable');
    }
}
