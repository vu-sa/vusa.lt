<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Octopy\Impersonate\Concerns\Impersonate;
use Octopy\Impersonate\ImpersonateAuthorization;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class User extends Authenticatable
{
    use Notifiable, HasFactory, HasRelationships, HasRoles, HasUlids, LogsActivity, SoftDeletes, Impersonate, Searchable;

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

    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
        ];
    }

    public function impersonatable(ImpersonateAuthorization $authorization): void
    {
        $authorization->impersonator(fn (User $user) => $user->hasRole(config('permission.super_admin_role_name')));

        $authorization->impersonated(fn (User $user) => ! $user->hasRole(config('permission.super_admin_role_name')));
    }

    // * The problem is that some models have different method names for getting the unit relation
    // * This returns the method name for the unit relation
    // ! It's better if user is extended from Authenticatable, not from Model, because impersonate package throws errors
    public function whichUnitRelation()
    {
        // check for padalinys relation
        if (method_exists($this, 'padalinys')) {
            return 'padalinys';
        }

        // check for padaliniai relation
        if (method_exists($this, 'padaliniai')) {
            return 'padaliniai';
        }

        // throw exception if no unit relation found
        throw new \Exception('No unit relation found');
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
            ->withPivot(['id', 'extra_attributes', 'start_date', 'end_date']);
    }

    public function previous_duties()
    {
        return $this->duties()
            ->where(function ($query) {
                $query->whereNotNull('dutiables.end_date')
                    ->where('dutiables.end_date', '<', now());
            })
            ->withTimestamps();
    }

    // this needs more debugging. don't use with withWhereHas
    // TODO: implement current_duties where appropriate
    public function current_duties()
    {
        return $this->duties()
            ->where(function ($query) {
                $query->whereNull('dutiables.end_date')
                    ->orWhere('dutiables.end_date', '>=', now());
            })
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
