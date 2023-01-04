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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use Notifiable, HasFactory, HasRoles, HasUlids, LogsActivity, SoftDeletes;

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
        return LogOptions::defaults();
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

    // TODO: return only current duties
    public function duties()
    {
        return $this->morphToMany(Duty::class, 'dutiable')
        ->using(Dutiable::class)->withPivot(['extra_attributes', 'start_date', 'end_date'])
        ->withTimestamps();
    }

    // TODO: more logical return of padalinys
    public function padalinys()
    {
        return $this->duties()->first()?->institution?->padalinys;
    }

    public function padaliniai()
    {
        $padaliniai = [];
        
        $this->load('duties.institution.padalinys');
        foreach ($this->duties as $duty) {
            if ($duty->institution->padalinys) {
                $padaliniai[] = $duty->institution->padalinys;
            }
        }

        // collect unique padaliniai
        $padaliniai = new Collection($padaliniai);

        return $padaliniai->unique();
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }
}
