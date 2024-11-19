<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use App\Models\Traits\HasTranslations;
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
    use Authorizable, HasFactory, HasRelationships, HasRoles, HasTranslations, HasUlids, LogsActivity, Notifiable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $with = ['types'];

    protected $guard_name = 'web';

    public $translatable = ['name', 'description'];

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
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
            ->withPivot(['start_date', 'end_date', 'additional_photo', 'additional_email', 'use_original_duty_name', 'description']);
    }

    // TODO: use current_duties as an example for current_users
    public function current_users()
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereNull('dutiables.end_date')
                    ->orWhere('dutiables.end_date', '>=', now());
            })
            ->withTimestamps();
    }

    public function previous_users()
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereNotNull('dutiables.end_date')
                    ->where('dutiables.end_date', '<', now());
            })
            ->withTimestamps();
    }

    public function contacts()
    {
        return $this->morphedByMany(Contact::class, 'dutiable')->using(Dutiable::class)->withTimestamps();
    }

    public function matters()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->matters());
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable')->using(Typeable::class)->withPivot(['typeable_type']);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function doings()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->doings());
    }

    // it has only one tenant all times, but it's better to have this method with this name
    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->tenant());
    }

    public function meetings()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->meetings());
    }

    public function agendaItems()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->meetings(), (new Meeting)->agendaItems());
    }

    // TODO: tasks should not be completable through duties, only by users
    public function tasks()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->tasks());
    }

    public function reservations()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->reservations());
    }

    public function resources()
    {
        return $this->hasManyDeepFromRelations($this->tenants(), (new Tenant)->resources());
    }
}
