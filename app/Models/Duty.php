<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Duty extends Model implements AuthorizableContract
{
    use HasFactory, Notifiable, Authorizable, HasRoles, HasRelationships, LogsActivity, HasUlids, SoftDeletes, Searchable;

    protected $with = ['types'];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    protected $guard_name = 'web';

    protected $guarded = [];

    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function dutiables()
    {
        return $this->hasMany(Dutiable::class);
    }

    public function users()
    {
        return $this->morphedByMany(User::class, 'dutiable')
            ->using(Dutiable::class)
            ->withPivot(['extra_attributes', 'start_date', 'end_date']);
    }

    // TODO: use current_duties as an example for current_users
    public function current_users()
    {
        return $this->users()
            ->wherePivot('end_date', null)->orWherePivot('end_date', '>=', now())
            ->withTimestamps();
    }

    public function previous_users()
    {
        return $this->users()
            ->wherePivot('end_date', '<', now())
            ->withTimestamps();
    }

    public function contacts()
    {
        return $this->morphedByMany(Contact::class, 'dutiable')->using(Dutiable::class)->withPivot(['extra_attributes', 'start_date'])->withTimestamps();
    }

    public function matters()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution())->matters());
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function doings()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->doings());
    }

    // it has only one padalinys all times, but it's better to have this method with this name
    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution())->padalinys());
    }

    public function meetings()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution())->meetings());
    }

    public function agendaItems()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution())->meetings(), (new Meeting())->agendaItems());
    }

    // TODO: tasks should not be completable through duties, only by users
    public function tasks()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->tasks());
    }

    public function reservations()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->reservations());
    }

    public function resources()
    {
        return $this->hasManyDeepFromRelations($this->padaliniai(), (new Padalinys())->resources());
    }

    // add "duty" relation which points to self
}
