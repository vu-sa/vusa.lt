<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A padalinys' planned goal for a term — part of the goals pilot (App\Support\Experiments\GoalsExperiment).
 *
 * @property string $id
 * @property int $tenant_id
 * @property string|null $cadence_id
 * @property string|null $responsible_duty_id
 * @property array|string $title
 * @property array|string|null $description
 * @property array|string|null $expected_result
 * @property array|string|null $evaluation
 * @property GoalStatus $status
 * @property bool $is_public
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Cadence|null $cadence
 * @property-read User|null $createdBy
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, Problem> $problems
 * @property-read Duty|null $responsibleDuty
 * @property-read Collection<int, Step> $steps
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\GoalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'tenant_id',
    'cadence_id',
    'responsible_duty_id',
    'title',
    'description',
    'expected_result',
    'evaluation',
    'status',
    'is_public',
    'created_by',
])]
class Goal extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['title', 'description', 'expected_result', 'evaluation'];

    protected function sanitizedHtmlTranslations(): array
    {
        return ['description', 'evaluation'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Cadence, $this> */
    public function cadence(): BelongsTo
    {
        return $this->belongsTo(Cadence::class);
    }

    /** @return BelongsTo<Duty, $this> */
    public function responsibleDuty(): BelongsTo
    {
        return $this->belongsTo(Duty::class, 'responsible_duty_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<Problem, $this> */
    public function problems(): BelongsToMany
    {
        return $this->belongsToMany(Problem::class)->withTimestamps();
    }

    /** @return HasMany<Step, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderByDesc('happened_on')->orderByDesc('created_at');
    }

    protected function casts(): array
    {
        return [
            'status' => GoalStatus::class,
            'is_public' => 'boolean',
        ];
    }
}
