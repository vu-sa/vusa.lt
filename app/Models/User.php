<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class User extends Authenticatable
{
    use Notifiable;


    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role_id', 'profile_photo_path'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Access banner with relationship

    public function banners()
    {
        return $this->hasMany(Banner::class, 'user_id', 'id');
    }

    public function calendar()
    {
        return $this->hasMany(Calendar::class, 'user_id', 'id');
    }

    public function duties()
    {
        return $this->belongsToMany(Duty::class, 'duties_users', 'user_id', 'duty_id');
    }

    public function institutions()
    {
        $duties = $this->duties;
        $institutions = [];

        foreach ($duties as $duty) {
            $institutions[] = $duty->institution;
        }

        // collect unique institutions

        $institutions = new Collection($institutions);

        return $institutions->unique();
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function isAdmin()
    {
        return $this->role?->alias == 'admin';
    }

    // if user role alias contains admin, return true
    // TODO: no need for this function, as policy will check before 'admin'
    public function isAdminOrSuperAdmin()
    {
        return $this->role?->alias == 'admin' || $this->role?->alias == 'padaliniai-admin';
    }

    // TODO: more logical return of padalinys
    public function padalinys()
    {
        return $this->duties()->first()?->institution?->padalinys;
    }

    public function padaliniai()
    {
        $institutions = $this->institutions();
        $padaliniai = [];

        foreach ($institutions as $institution) {
            $padaliniai[] = $institution->padalinys;
        }

        // collect unique padaliniai
        $padaliniai = new Collection($padaliniai);

        return $padaliniai->unique();
    }
}
