<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use App\Models\Pivots\Trainable;
use App\Models\Traits\HasTranslations;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property array<array-key, mixed>|null $name
 * @property array<array-key, mixed>|null $description
 * @property string $institution_id
 * @property int $order Order of duty in institution
 * @property string|null $email Commonly the @vusa.lt email address, which is used as the OAuth login. Personal mail is stored in users.email.
 * @property string $contacts_grouping
 * @property int|null $places_to_occupy Full number of positions to occupy for this duty
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pivots\AgendaItem> $agendaItems
 * @property-read \App\Models\Typeable|Dutiable|Trainable|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Training> $availableTrainings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $current_users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Dutiable> $dutiables
 * @property-read \App\Models\Institution|null $institution
 * @property-read \App\Models\Institution|null $institutions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $previous_users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resource> $resources
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 * @property-read mixed $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Type> $types
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @method static \Database\Factories\DutyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withoutTrashed()
 * @mixin \Eloquent
 */
class Duty extends Model implements AuthorizableContract
{
    use Authorizable, HasFactory, HasRelationships, HasRoles, HasTranslations, HasUlids, LogsActivity, Notifiable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $fillable = [
        'name', 'description', 'email', 'phone', 'order', 'is_active', 'institution_id', 'contacts_grouping',
    ];

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

    public function users(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(User::class, 'dutiable')
            ->using(Dutiable::class)
            ->withPivot(['id', 'start_date', 'end_date', 'additional_photo', 'additional_email', 'use_original_duty_name', 'description', 'study_program_id']);
    }

    // TODO: use current_duties as an example for current_users
    public function current_users(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereNull('dutiables.end_date')
                    ->orWhere('dutiables.end_date', '>=', now());
            })
            ->withTimestamps();
    }

    public function previous_users(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereNotNull('dutiables.end_date')
                    ->where('dutiables.end_date', '<', now());
            })
            ->withTimestamps();
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable')->using(Typeable::class)->withPivot(['typeable_type']);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    // HACK: this is a workaround for the fact that we cannot use the same relation name in the parent and child models
    public function institutions(): BelongsTo
    {
        return $this->institution();
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

    public function availableTrainings()
    {
        return $this->morphToMany(Training::class, 'trainable')->using(Trainable::class);
    }
}
