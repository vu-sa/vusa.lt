<?php

namespace App\Models\Pivots;

use App\Models\Institution;
use App\Models\Matter;
use App\Models\Meeting;
use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class AgendaItem extends Pivot
{
    use HasFactory, HasRelationships, HasUlids, LogsActivity;

    protected $table = 'agenda_items';

    protected $touches = ['meeting'];

    public $incrementing = true;

    protected static function newFactory(): Factory
    {
        return AgendaItemFactory::new();
    }

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
        return $this->hasManyDeepFromRelations($this->meeting(), (new Meeting)->institutions());
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->padalinys());
    }
}
