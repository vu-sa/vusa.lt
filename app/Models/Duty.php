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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Scout\EngineManager;
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
 * @property string|null $selection_method
 * @property array|string|null $appointed_by
 * @property array|string|null $term_length
 * @property array|string|null $responsibilities
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, AgendaItem> $agendaItems
 * @property-read Collection<int, Tenant> $assignableTenants
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Typeable|Dutiable|Trainable|null $pivot
 * @property-read Collection<int, Training> $availableTrainings
 * @property-read Collection<int, User> $current_users
 * @property-read Collection<int, Dutiable> $dutiables
 * @property-read Collection<int, Duty> $exOfficioSourceDuties
 * @property-read Collection<int, Duty> $exOfficioTargetDuties
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read array $translatable_columns_from
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
 * @property-read Collection<int, Permission> $teams
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty team($teams, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withoutRole($roles, ?string $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Duty withoutTeam($teams)
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
        'selection_method', 'appointed_by', 'term_length', 'responsibilities',
    ];

    // Note: types are NOT auto-loaded to prevent N+1 in collections.
    // Load explicitly where needed: ->with('duties.types') or ->load('types').

    protected $guard_name = 'web';

    public $translatable = ['name', 'description', 'appointed_by', 'term_length', 'responsibilities'];

    /**
     * Resolve the appointment metadata for this duty, falling back to the
     * institution's defaults when the duty does not override a value.
     *
     * @return array{selection_method: string|null, appointed_by: string|null, term_length: string|null}
     */
    public function resolveAppointment(): array
    {
        $institution = $this->institution;

        $selectionMethod = $this->selection_method ?: $institution?->selection_method;

        $appointedBy = filled($this->appointed_by) ? $this->appointed_by : $institution?->appointed_by;
        $termLength = filled($this->term_length) ? $this->term_length : $institution?->term_length;

        return [
            'selection_method' => $selectionMethod ?: null,
            'appointed_by' => $appointedBy ?: null,
            'term_length' => $termLength ?: null,
        ];
    }

    /**
     * Get the engine used to index the model.
     *
     * Admin search runs on Typesense (scoped keys), so force the typesense
     * engine even though the default Scout driver is the database engine.
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('typesense');
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['institution.tenant', 'types', 'assignableTenants']);

        $homeTenantId = $this->institution?->tenant_id ? (int) $this->institution->tenant_id : null;

        // tenant_ids drives the scoped-key access filter. It combines the duty's
        // home tenant with any tenants it is assignable to, so cross-tenant admins
        // can find duties they may staff (mirrors DutyController@index OR-clause).
        $tenantIds = $this->assignableTenants
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->when($homeTenantId !== null, fn ($ids) => $ids->push($homeTenantId))
            ->unique()
            ->values()
            ->all();

        $currentUsersCount = $this->current_users()->count();
        $currentUsers = $this->current_users()->limit(12)->get(['users.id', 'name']);
        $previousUsers = $this->previous_users()
            ->orderByPivot('end_date', 'desc')
            ->limit(6)
            ->get(['users.id', 'name']);

        return [
            'id' => (string) $this->id,
            'name_lt' => $this->getTranslation('name', 'lt'),
            'name_en' => $this->getTranslation('name', 'en'),
            'email' => $this->email,
            'tenant_ids' => $tenantIds,
            'home_tenant_id' => $homeTenantId,
            'tenant_shortname' => $this->institution?->tenant?->shortname,
            'institution_id' => $this->institution_id ? (string) $this->institution_id : null,
            'institution_name_lt' => $this->institution?->getTranslation('name', 'lt'),
            'institution_name_en' => $this->institution?->getTranslation('name', 'en'),
            'type_titles' => $this->types
                ->map(fn (Type $type) => $type->getTranslation('title', 'lt'))
                ->filter()
                ->values()
                ->all(),
            'current_user_names' => $currentUsers->pluck('name')->values()->all(),
            'current_users_count' => $currentUsersCount,
            'previous_user_names' => $previousUsers->pluck('name')->values()->all(),
            'created_at' => $this->created_at?->timestamp,
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
            ->withPivot(['id', 'via_dutiable_id', 'tenant_id', 'start_date', 'end_date', 'additional_photo', 'additional_photo_focal_point', 'additional_email', 'use_original_duty_name', 'description', 'study_program_id']);
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

    /** Duties that are automatically granted when a user holds this duty. */
    public function exOfficioTargetDuties(): BelongsToMany
    {
        return $this->belongsToMany(Duty::class, 'ex_officio_duties', 'source_duty_id', 'target_duty_id');
    }

    /** Duties that automatically grant this duty. */
    public function exOfficioSourceDuties(): BelongsToMany
    {
        return $this->belongsToMany(Duty::class, 'ex_officio_duties', 'target_duty_id', 'source_duty_id');
    }

    /** Additional tenants whose admins may assign their own reps to this duty, with per-tenant quotas. */
    public function assignableTenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'duty_tenant')->withPivot(['quota'])->withTimestamps();
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
