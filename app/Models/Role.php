<?php

namespace App\Models;

use App\Enums\ResponsibilityPurpose;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use App\Models\Traits\LogsRelationshipChanges;
use App\Services\ResponsibilityResolver;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Models\Role as SpatieRole;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * An atsakomybė (responsibility): what a duty does, the permissions that work needs and, for a
 * few, a system purpose the app finds its holders by. Spatie calls it a role; `name` is its
 * stable key, `title` what people read.
 *
 * @property string $id
 * @property string $name
 * @property array|string|null $title
 * @property array|string|null $description
 * @property ResponsibilityPurpose|null $purpose
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DutyType> $attachable_types
 * @property-read Collection<int, User> $currentUsersThroughDuties
 * @property-read Collection<int, Duty> $duties
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, ResponsibilityGrant> $grants
 * @property-read Collection<int, Permission> $permissions
 * @property-read mixed $translations
 * @property-read Collection<int, DutyType> $types
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, User> $usersThroughDuties
 * @property-read int|null $users_through_duties_count
 * @property-read int|null $current_users_through_duties_count
 *
 * @method static Builder<static>|Role exceptSuperAdmin()
 * @method static \Database\Factories\RoleFactory factory($count = null, $state = [])
 * @method static Builder<static>|Role newModelQuery()
 * @method static Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, bool $without = false)
 * @method static Builder<static>|Role query()
 * @method static Builder<static>|Role whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Role whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Role whereLocale(string $column, string $locale)
 * @method static Builder<static>|Role whereLocales(string $column, array $locales)
 * @method static Builder<static>|Role whereTranslationLike(array|string $columns, string $search, ?array $locales = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 *
 * @mixin \Eloquent
 */
class Role extends SpatieRole
{
    use HasFactory, HasRelationships, HasTranslations, HasUlids, LogsModelActivity, LogsRelationshipChanges;

    /** @var list<string> */
    public $translatable = ['title', 'description'];

    #[\Override]
    protected static function booted(): void
    {
        static::saved(fn () => app(ResponsibilityResolver::class)->flush());
        static::deleted(fn () => app(ResponsibilityResolver::class)->flush());
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return ['purpose' => ResponsibilityPurpose::class];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->dontLogEmptyChanges()
            ->logOnly(['title', 'description'])
            ->useAttributeRawValues(['title', 'description']);
    }

    public function displayDescription(?string $locale = null): ?string
    {
        $translations = $this->getTranslations('description');

        return ($translations[$locale ?? app()->getLocale()] ?? null) ?: (($translations['lt'] ?? null) ?: null);
    }

    public static function forPurpose(ResponsibilityPurpose $purpose): ?self
    {
        return self::query()->where('purpose', $purpose)->first();
    }

    /**
     * A unique key for a new responsibility, derived from its Lithuanian title.
     */
    public static function generateName(string $title): string
    {
        $base = Str::slug($title) ?: 'atsakomybe';
        $name = $base;

        for ($suffix = 2; self::query()->where('name', $name)->exists(); $suffix++) {
            $name = "{$base}-{$suffix}";
        }

        return $name;
    }

    public function displayTitle(?string $locale = null): string
    {
        $title = $this->getTranslation('title', $locale ?? app()->getLocale(), useFallbackLocale: true);

        return is_string($title) && $title !== '' ? $title : $this->name;
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === config('permission.super_admin_role_name');
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function exceptSuperAdmin(Builder $query): void
    {
        $query->where('name', '!=', config('permission.super_admin_role_name'));
    }

    /** @return HasMany<ResponsibilityGrant, $this> */
    public function grants(): HasMany
    {
        return $this->hasMany(ResponsibilityGrant::class);
    }

    /**
     * Every duty holding it, however it was granted (the `model_has_roles` projection).
     *
     * @return MorphToMany<Duty, $this>
     */
    public function duties(): MorphToMany
    {
        return $this->morphedByMany(Duty::class, 'model', 'model_has_roles');
    }

    public function usersThroughDuties(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->users());
    }

    public function currentUsersThroughDuties(): HasManyDeep
    {
        return $this->usersThroughDuties()
            ->where(function ($query): void {
                $query->whereDate('dutiables.start_date', '<=', now()->toDateString())
                    ->where(function ($q): void {
                        $q->whereNull('dutiables.end_date')
                            ->orWhereDate('dutiables.end_date', '>=', today());
                    });
            });
    }

    /**
     * Duty types its holders may put on duties (delegation, not a grant).
     */
    public function attachable_types(): BelongsToMany
    {
        return $this->belongsToMany(DutyType::class, 'role_can_attach_duty_types');
    }

    /**
     * Duty types granting it to every duty of the type.
     */
    public function types(): MorphToMany
    {
        return $this->morphedByMany(DutyType::class, 'grantee', 'responsibility_grants', 'role_id', 'grantee_id');
    }
}
