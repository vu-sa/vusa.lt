<?php

namespace App\Models;

use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Doable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Matter extends Model
{
    use HasFactory, HasRelationships, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $casts = [
        'created_at' => 'timestamp',
    ];

    protected $guarded = [];

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function institutions()
    {
        return $this->belongsToMany(Institution::class, 'institutions_matters', 'matter_id', 'institution_id');
    }
    
    /**
     * Get the primary institution associated with this matter
     * This is a convenience method that returns the first related institution
     */
    public function institution()
    {
        return $this->belongsToMany(Institution::class, 'institutions_matters', 'matter_id', 'institution_id')
            ->limit(1);
    }

    public function meetings()
    {
        return $this->belongsToMany(Meeting::class, 'agenda_items')->using(AgendaItem::class);
    }

    public function doings()
    {
        return $this->morphToMany(Doing::class, 'doable', 'doables')->using(Doable::class)->withTimestamps();
    }

    public function goals()
    {
        return $this->belongsToMany(Goal::class);
    }

    public function users()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->duties(), (new Duty)->users());
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->tenant());
    }
}
