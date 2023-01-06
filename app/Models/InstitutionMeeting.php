<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class InstitutionMeeting extends Model
{
    use HasFactory, HasUlids, HasRelationships, LogsActivity, SoftDeletes;

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

    public function institutions()
    {
        return $this->hasManyDeepFromRelations($this->matters(), (new InstitutionMatter())->institution())->distinct();
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
