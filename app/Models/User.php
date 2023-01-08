<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Dutiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
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
        'last_login',
        'microsoft_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    // Access banner with relationship

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
            ->wherePivot('end_date', '>=', now())->orWherePivot('end_date', null)
            ->withTimestamps();
    }

    // TODO: more logical return of padalinys
    public function padalinys()
    {
        return $this->duties()->first()?->institution?->padalinys;
    }

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty())->institution(), (new Institution())->padalinys());
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }
}
