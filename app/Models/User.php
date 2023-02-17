<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use Notifiable, HasFactory, HasRelationships, HasRoles, HasUlids, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'profile_photo_path'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'email_verified_at',
        'last_action',
        'microsoft_token',
    ];

    protected $casts = [
        'last_action' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function banners()
    {
        return $this->hasMany(Banner::class, 'user_id', 'id');
    }

    public function calendar()
    {
        return $this->hasMany(Calendar::class, 'user_id', 'id');
    }

    public function doings()
    {
        return $this->belongsToMany(Doing::class);
    }

    public function duties()
    {
        return $this->morphToMany(Duty::class, 'dutiable')
            ->using(Dutiable::class)
            ->withPivot(['extra_attributes'])
            // TODO: when used in relations, this returns all...
            // ->wherePivot('end_date', '>=', now())->orWherePivot('end_date', null)
            ->withTimestamps();
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty())->institution(), (new Institution())->padalinys());
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function institutions()
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty())->institution());
    }
}
