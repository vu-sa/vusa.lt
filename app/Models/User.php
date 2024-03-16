<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use App\Models\Traits\HasUnitRelation;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Laravel\Scout\Searchable;
use Octopy\Impersonate\Authorization;
use Octopy\Impersonate\Concerns\HasImpersonation;
use Octopy\Impersonate\Http\Resources\ImpersonateResource;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class User extends Authenticatable
{
    use HasFactory, HasRelationships, HasRoles, HasUlids, HasUnitRelation, HasImpersonation, LogsActivity, Notifiable, Searchable, SoftDeletes;

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

    public function getImpersonateDisplayText(): string
    {
        return $this->name;
    }

    public function getImpersonateSearchField() : array
    {
        return [
            'name', 'email',
        ];
    }

    public function setImpersonateAuthorization(Authorization $authorization): void
    {
        $authorization->impersonator(fn (User $user) => $user->hasRole(config('permission.super_admin_role_name')));

        $authorization->impersonated(function ($impersonateResource)
            { 
                if ($impersonateResource::class === ImpersonateResource::class) {
                    $impersonateResource = $impersonateResource->resource;
                }

                return ! $impersonateResource->hasRole(config('permission.super_admin_role_name'));
            });
    }

    /**
     * If the user has a duty, always send to current_duties if duty email ends with vusa.lt
     * More on this: https://laravel.com/docs/10.x/notifications#customizing-the-recipient
     * TODO: it is not really optimal as sometimes notifications should be sent directly to user
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        if ($this->current_duties()->count() > 0) {
            foreach ($this->current_duties()->get() as $duty) {
                if (str_ends_with($duty->email, 'vusa.lt')) {
                    return $duty->email;
                }
            }
        }

        return $this->email;
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
