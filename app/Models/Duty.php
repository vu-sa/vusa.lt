<?php

namespace App\Models;

use App\Contracts\SharepointFileableContract;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\Pivots\Trainable;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property array|string|null $name
 * @property array|string|null $description
 * @property string $institution_id
 * @property int $order Order of duty in institution
 * @property string|null $email Commonly the @vusa.lt email address, which is used as the OAuth login. Personal mail is stored in users.email.
 * @property string $contacts_grouping
 * @property int|null $places_to_occupy Full number of positions to occupy for this duty
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, AgendaItem> $agendaItems
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Typeable|Dutiable|Trainable|null $pivot
 * @property-read Collection<int, Training> $availableTrainings
 * @property-read Collection<int, User> $current_users
 * @property-read Collection<int, Dutiable> $dutiables
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read Collection<int, SharepointFile> $files
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read Institution|null $institution
 * @property-read Institution|null $institutions
 * @property-read Collection<int, Meeting> $meetings
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, User> $previous_users
 * @property-read Collection<int, Reservation> $reservations
 * @property-read Collection<int, \App\Models\Resource> $resources
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Task> $tasks
 * @property-read Collection<int, Tenant> $tenants
 * @property-read mixed $translations
 * @property-read Collection<int, Type> $types
 * @property-read Collection<int, User> $users
 * @property-read int|null $tenants_count
 * @property-read int|null $meetings_count
 * @property-read int|null $agenda_items_count
 * @property-read int|null $tasks_count
 * @property-read int|null $reservations_count
 * @property-read int|null $resources_count
 *
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
 *
 * @mixin \Eloquent
 */
class Duty extends Model implements AuthorizableContract, SharepointFileableContract
{
    use Authorizable, HasFactory, HasRelationships, HasRoles, HasSharepointFiles, HasTranslations, HasUlids, LogsActivity, Notifiable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $fillable = [
        'name', 'description', 'email', 'phone', 'order', 'is_active', 'institution_id', 'contacts_grouping', 'places_to_occupy',
    ];

    // Note: types are NOT auto-loaded to prevent N+1 in collections.
    // Load explicitly where needed: ->with('duties.types') or ->load('types').

    protected $guard_name = 'web';

    public $translatable = ['name', 'description'];

    public function toSearchableArray(): array
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

    public function dutiables(): HasMany
    {
        return $this->hasMany(Dutiable::class);
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'dutiable')
            ->using(Dutiable::class)
            ->withPivot(['id', 'start_date', 'end_date', 'additional_photo', 'additional_email', 'use_original_duty_name', 'description', 'study_program_id']);
    }

    // TODO: use current_duties as an example for current_users
    public function current_users(): MorphToMany
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereNull('dutiables.end_date')
                    ->orWhere('dutiables.end_date', '>=', now());
            })
            ->withTimestamps();
    }

    public function previous_users(): MorphToMany
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereNotNull('dutiables.end_date')
                    ->where('dutiables.end_date', '<', now());
            })
            ->withTimestamps();
    }

    public function types(): MorphToMany
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
    public function tenants(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->tenant());
    }

    public function meetings(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->meetings());
    }

    public function agendaItems(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->meetings(), (new Meeting)->agendaItems());
    }

    // TODO: tasks should not be completable through duties, only by users
    public function tasks(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->tasks());
    }

    public function reservations(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->reservations());
    }

    public function resources(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->tenants(), (new Tenant)->resources());
    }

    public function availableTrainings()
    {
        return $this->morphToMany(Training::class, 'trainable')->using(Trainable::class);
    }

    protected static function booted()
    {
        static::saving(function (Duty $duty) {
            // Dispatch event when name is about to change - SharePoint must succeed first
            if ($duty->isDirty('name')) {
                FileableNameUpdated::dispatch($duty);
            }
        });
    }
}
