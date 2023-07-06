<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Octopy\Impersonate\Concerns\Impersonate;
use Octopy\Impersonate\ImpersonateAuthorization;

class User extends Authenticatable
{
    use Notifiable, HasFactory, HasRelationships, HasRoles, HasUlids, LogsActivity, SoftDeletes, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'profile_photo_path',
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
        'last_changelog_check',
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

    public function impersonatable(ImpersonateAuthorization $authorization): void
    {
        $authorization->impersonator(fn (User $user) => $user->hasRole(config('permission.super_admin_role_name')));

        $authorization->impersonated(fn (User $user) => !$user->hasRole(config('permission.super_admin_role_name')));
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
            ->withPivot(['extra_attributes', 'start_date', 'end_date']);
    }

    public function previous_duties()
    {
        return $this->duties()
            ->wherePivot('end_date', '<', now())
            ->withTimestamps();
    }

    // this needs more debugging. don't use with withWhereHas

    public function current_duties()
    {
        return $this->duties()
            ->wherePivotNull('end_date')->orWherePivot('end_date', '>=', now())
            ->withTimestamps();
    }

    public function dutiables()
    {
        return $this->morphMany(Dutiable::class, 'dutiable');
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

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->withTimestamps();
    }
}
