<?php

namespace App\Models;

use App\Actions\ProjectDutyResponsibilities;
use App\Enums\ResponsibilityScope;
use App\Models\Traits\LogsModelActivity;
use App\Services\ResponsibilityResolver;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A responsibility given to a duty, to every duty of a type, or to every duty in an institution.
 *
 * The source of truth for duty roles: `model_has_roles` rows for duties are its projection.
 * A tenant scope without a `scope_id` covers each reached duty's own padalinys.
 *
 * @property string $id
 * @property string $role_id
 * @property string $grantee_type
 * @property string $grantee_id
 * @property string|null $scope_type
 * @property string|null $scope_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read \Illuminate\Database\Eloquent\Model $grantee
 * @property-read Role $role
 * @property-read \Illuminate\Database\Eloquent\Model|null $scope
 *
 * @method static \Database\Factories\ResponsibilityGrantFactory factory($count = null, $state = [])
 * @method static Builder<static>|ResponsibilityGrant newModelQuery()
 * @method static Builder<static>|ResponsibilityGrant newQuery()
 * @method static Builder<static>|ResponsibilityGrant payload()
 * @method static Builder<static>|ResponsibilityGrant query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['role_id', 'grantee_type', 'grantee_id', 'scope_type', 'scope_id'])]
class ResponsibilityGrant extends Model
{
    use HasFactory, HasUlids, LogsModelActivity;

    /** @var list<class-string<\Illuminate\Database\Eloquent\Model>> */
    public const array GRANTEES = [Duty::class, DutyType::class, Institution::class];

    /** The scope token forms and payloads use for a tenant scope without a fixed padalinys. */
    public const string OWN_TENANT = 'own_tenant';

    #[\Override]
    protected static function booted(): void
    {
        static::saved(fn (self $grant) => $grant->reproject());
        static::deleted(fn (self $grant) => $grant->reproject());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function grantee(): MorphTo
    {
        return $this->morphTo('grantee', 'grantee_type', 'grantee_id');
    }

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function scope(): MorphTo
    {
        return $this->morphTo('scope', 'scope_type', 'scope_id');
    }

    public function coverage(): ?ResponsibilityScope
    {
        return $this->scope_type === null ? null : ResponsibilityScope::from($this->scope_type);
    }

    public function coversOwnTenant(): bool
    {
        return $this->scope_type === ResponsibilityScope::Tenant->value && $this->scope_id === null;
    }

    public function needsScope(): bool
    {
        return $this->role->purpose?->isScoped() === true
            && (($this->scope === null && ! $this->coversOwnTenant()) || ! in_array(ResponsibilityScope::tryFrom($this->scope_type ?? ''), $this->role->purpose->allowedScopes(), true));
    }

    /**
     * @return array{scope_type: string|null, scope_name: string|null}
     */
    public function scopePayload(): array
    {
        if ($this->coversOwnTenant()) {
            return ['scope_type' => self::OWN_TENANT, 'scope_name' => null];
        }

        return [
            'scope_type' => $this->scope_type,
            'scope_name' => match (true) {
                $this->scope instanceof Tenant => (string) __($this->scope->shortname),
                $this->scope instanceof InstitutionType => (string) $this->scope->getTranslation('title', app()->getLocale()),
                $this->scope instanceof Institution => (string) $this->scope->getTranslation('name', app()->getLocale()),
                default => null,
            },
        ];
    }

    /**
     * Ids of the duties these grants reach: direct ones, every duty of a granted type or its
     * subtypes, every duty in a granted institution.
     *
     * @param  Builder<self>  $grants
     */
    public static function dutyIdsQuery(Builder $grants): QueryBuilder
    {
        $duty = MorphMap::alias(Duty::class);
        $dutyType = MorphMap::alias(DutyType::class);
        $institution = MorphMap::alias(Institution::class);

        $direct = (clone $grants)->toBase()->where('grantee_type', $duty)->select('grantee_id as duty_id');

        $viaType = DB::table('duty_duty_type')
            ->whereIn('duty_type_id', DutyType::withDescendantIds((clone $grants)->toBase()->where('grantee_type', $dutyType)->pluck('grantee_id')->all()))
            ->select('duty_id');

        $viaInstitution = DB::table('duties')
            ->whereIn('institution_id', (clone $grants)->toBase()->where('grantee_type', $institution)->select('grantee_id'))
            ->select('id as duty_id');

        return $direct->union($viaType)->union($viaInstitution);
    }

    private function reproject(): void
    {
        app(ResponsibilityResolver::class)->flush();

        $dutyIds = match ($this->grantee_type) {
            MorphMap::alias(Duty::class) => [$this->grantee_id],
            MorphMap::alias(DutyType::class) => DB::table('duty_duty_type')
                ->whereIn('duty_type_id', [(int) $this->grantee_id, ...DutyType::withDescendantIds([$this->grantee_id])])
                ->pluck('duty_id')->all(),
            MorphMap::alias(Institution::class) => DB::table('duties')->where('institution_id', $this->grantee_id)->pluck('id')->all(),
            default => [],
        };

        ProjectDutyResponsibilities::forDuties($dutyIds);
    }
}
