<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AgendaItem extends Pivot
{
    use HasUlids, LogsActivity;
    
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
}
