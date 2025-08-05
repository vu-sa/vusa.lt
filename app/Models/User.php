<?php

namespace App\Models;

use App\Helpers\AddressivizeHelper;
use App\Models\Pivots\Dutiable;
use App\Models\Pivots\MembershipUser;
use App\Models\Pivots\Trainable;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasUnitRelation;
use Illuminate\Database\Eloquent\Collection;
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

/**
 * @property string $id
 * @property string $email
 * @property string|null $phone
 * @property string|null $facebook_url
 * @property string $name
 * @property array<array-key, mixed>|null $pronouns
 * @property bool $show_pronouns
 * @property string|null $password
 * @property int $is_active
 * @property string|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $last_action
 * @property string|null $last_changelog_check
 * @property string|null $microsoft_token
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property string|null $profile_photo_path
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $name_was_changed
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read MembershipUser|Dutiable|Trainable|null $pivot
 * @property-read Collection<int, \App\Models\Training> $availableTrainingsThroughUser
 * @property-read Collection<int, \App\Models\Duty> $current_duties
 * @property-read Collection<int, Dutiable> $dutiables
 * @property-read Collection<int, \App\Models\Duty> $duties
 * @property-read mixed $has_password
 * @property-read Collection<int, \App\Models\Institution> $institutions
 * @property-read Collection<int, \App\Models\Membership> $memberships
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read Collection<int, \App\Models\Permission> $permissions
 * @property-read Collection<int, \App\Models\Duty> $previous_duties
 * @property-read Collection<int, \App\Models\Reservation> $reservations
 * @property-read Collection<int, \App\Models\Role> $roles
 * @property-read Collection<int, \App\Models\Task> $tasks
 * @property-read Collection<int, \App\Models\Tenant> $tenants
 * @property-read Collection<int, \App\Models\Training> $trainings
 * @property-read mixed $translations
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, HasImpersonation, HasRelationships, HasRoles, HasTranslations, HasUlids, HasUnitRelation, LogsActivity, Notifiable, Searchable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'facebook_url', 'password', 'phone', 'profile_photo_path', 'pronouns', 'show_pronouns',
    ];

    public $translatable = [
        'pronouns',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'last_changelog_check',
        'last_action',
        'microsoft_token',
        'name_was_changed',
    ];

    protected $casts = [
        'last_action' => 'datetime',
        'show_pronouns' => 'boolean',
        'name_was_changed' => 'boolean',
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

    public function getImpersonateSearchField(): array
    {
        return [
            'name', 'email',
        ];
    }

    public function setImpersonateAuthorization(Authorization $authorization): void
    {
        $authorization->impersonator(fn (User $user) => $user->hasRole(config('permission.super_admin_role_name')));

        $authorization->impersonated(function ($impersonateResource) {
            if ($impersonateResource::class === ImpersonateResource::class) {
                $impersonateResource = $impersonateResource->resource;
            }

            return ! $impersonateResource->hasRole(config('permission.super_admin_role_name'));
        });
    }

    /**
     * Check if user has a password set
     */
    public function getHasPasswordAttribute()
    {
        return ! empty($this->getAttributeValue('password'));
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

    public function duties(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Duty::class, 'dutiable')
            ->using(Dutiable::class)
            ->withPivot(['id', 'start_date', 'end_date', 'additional_photo', 'additional_email', 'use_original_duty_name', 'description']);
    }

    public function previous_duties(): \Illuminate\Database\Eloquent\Relations\MorphToMany
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
    public function current_duties(): \Illuminate\Database\Eloquent\Relations\MorphToMany
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

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->institution(), (new Institution)->tenant());
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function institutions()
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->institution());
    }

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->withTimestamps();
    }

    public function memberships()
    {
        return $this->belongsToMany(Membership::class)->using(MembershipUser::class)->withTimestamps()->withPivot('start_date', 'end_date');
    }

    // TODO: refactor to use the new method
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('permission.super_admin_role_name'));
    }

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'training_user')->withTimestamps();
    }

    public function availableTrainingsThroughUser()
    {
        return $this->morphToMany(Training::class, 'trainable')->using(Trainable::class)->withTimestamps();
    }

    /**
     * @return Collection<Training>
     */
    public function allAvailableTrainings()
    {
        $avThDuty = $this->load('current_duties.availableTrainings')->current_duties->map(function ($duty) {
            return $duty->availableTrainings;
        })->flatten();

        $avThUser = $this->availableTrainingsThroughUser()->get();

        $avThInstitution = $this->load('institutions.availableTrainings')->institutions->map(function ($institution) {
            return $institution->availableTrainings;
        })->flatten();

        $avThMembership = $this->load('memberships.availableTrainings')->memberships->map(function ($membership) {
            return $membership->availableTrainings;
        })->flatten();

        return $avThDuty->merge($avThUser)->merge($avThInstitution)->merge($avThMembership)->unique('id');
    }

    public function addressivizedName(): string
    {
        return AddressivizeHelper::addressivizeEveryWord($this->name);
    }
}
