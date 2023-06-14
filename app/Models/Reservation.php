<?php

namespace App\Models;

use App\Models\Traits\HasComments;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Reservation extends Model
{
    use HasFactory, HasStates, HasComments, HasTranslations, HasRelationships, HasUlids, LogsActivity, SoftDeletes;

    protected $guarded = [];

    public $translatable = ['name', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    // reservation state is calculated from the pivot

    public function resources()
    {
        return $this->belongsToMany(Resource::class)
            ->withPivot(['start_time', 'end_time', 'quantity', 'state', 'returned_at'])
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->padaliniai());
    }
}
