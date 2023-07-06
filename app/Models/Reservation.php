<?php

namespace App\Models;

use App\Models\Pivots\ReservationResource;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Reservation extends Model
{
    use HasFactory, HasComments, HasTranslations, HasRelationships, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    public $translatable = ['name', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', 'lt'),
            // 'description->'.app()->getLocale() => $this->getTranslation('description', 'lt'),
        ];
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class)->using(ReservationResource::class)
            ->withPivot(['id', 'start_time', 'end_time', 'quantity', 'state', 'returned_at'])
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
