<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';
    public $timestamps = false;

    // Functions

    public function isAdmin() {
        return Auth::user()->gid == 1;
    }

    public function isCB() {
        return Auth::user()->gid < 4;
    }

    public function isSaziningai() {
        return Auth::user()->gid == 19;
    }

    public function isCommunication() {
        return Auth::user()->gid != 3 && Auth::user()->gid != 19;
    }

    public function isPadaliniaiCommunication() {
        return Auth::user()->gid > 3 && Auth::user()->gid != 19;
    }
}