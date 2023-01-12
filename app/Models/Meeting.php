<?php

namespace App\Models;

use App\Models\Pivots\AgendaItem;
use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Meeting extends Model
{
    use HasFactory, HasComments, HasUlids, HasRelationships, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'start_time' => 'timestamp',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function matters()
    {
        return $this->belongsToMany(Matter::class, 'agenda_items')->using(AgendaItem::class);
    }

    public function agendaItems()
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function institutions()
    {
        return $this->belongsToMany(Institution::class);
    }

    public function documents()
    {
        return $this->morphMany(SharepointDocument::class, 'documentable');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function users()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->users());
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->padalinys());
    }
}
