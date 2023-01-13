<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class AgendaItem extends Pivot
{
    use HasUlids, HasRelationships, LogsActivity;

    protected $table = 'agenda_items';
    
    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
    // TODO: it's from the meeting side, although agendaItems can be accessed from matters also,
    // but it's less logical that way
    public function institutions()
    {
        return $this->hasManyThrough(Institution::class, Meeting::class);
    }

    public function padaliniai()
    {
        return $this->hasManyDeep(
            Padalinys::class,
            [Meeting::class, Institution::class],
            ['id', 'id', 'id'],
            ['meeting_id', 'institution_id', 'id'],
            ['id', 'id', 'id']
        );
    }
}
