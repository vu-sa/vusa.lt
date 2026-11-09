<?php

namespace App\Models;

use App\Contracts\Commentable;
use App\Enums\LearningImportance;
use App\Enums\LearningLayer;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $id
 * @property string|null $key
 * @property bool $is_system
 * @property LearningStepKind $kind
 * @property LearningLayer $layer
 * @property LearningStage|null $stage
 * @property LearningImportance $importance
 * @property int|null $institution_type_id
 * @property int|null $duty_type_id
 * @property int|null $tenant_id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property bool $per_term
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $reviewed_at
 * @property string|null $created_by
 * @property-read int $comments_count
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, LearningStepSource> $sources
 * @property-read Collection<int, LearningStepProgress> $progress
 * @property-read InstitutionType|null $institutionType
 * @property-read DutyType|null $dutyType
 * @property-read Tenant|null $tenant
 * @property-read Collection<int, GlossaryTerm> $glossaryTerms
 * @property-read User|null $createdBy
 */
#[Fillable(['key', 'is_system', 'kind', 'layer', 'stage', 'importance', 'institution_type_id', 'duty_type_id', 'tenant_id', 'title', 'description', 'per_term', 'order', 'is_active', 'reviewed_at', 'created_by'])]
class LearningStep extends Model implements Commentable
{
    use HasComments, HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['title', 'description'];

    /** @return HasMany<LearningStepSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(LearningStepSource::class);
    }

    /** @return HasMany<LearningStepProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(LearningStepProgress::class);
    }

    /** @return BelongsTo<InstitutionType, $this> */
    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }

    /** @return BelongsTo<DutyType, $this> */
    public function dutyType(): BelongsTo
    {
        return $this->belongsTo(DutyType::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsToMany<GlossaryTerm, $this> */
    public function glossaryTerms(): BelongsToMany
    {
        return $this->belongsToMany(GlossaryTerm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return ['kind' => LearningStepKind::class, 'layer' => LearningLayer::class, 'stage' => LearningStage::class, 'importance' => LearningImportance::class, 'is_system' => 'boolean', 'per_term' => 'boolean', 'is_active' => 'boolean', 'reviewed_at' => 'datetime', 'order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $step): void {
            $target = match ($step->layer) {
                LearningLayer::InstitutionType => 'institution_type_id',
                LearningLayer::DutyType => 'duty_type_id',
                LearningLayer::Tenant => 'tenant_id',
                default => null,
            };
            foreach (['institution_type_id', 'duty_type_id', 'tenant_id'] as $column) {
                if ($column !== $target) {
                    $step->{$column} = null;
                }
            }
            if ($target !== null && $step->{$target} === null) {
                throw ValidationException::withMessages([$target => __('validation.required', ['attribute' => $target])]);
            }
            $valid = $step->kind === LearningStepKind::MeetingPrep
                ? $step->layer === LearningLayer::InstitutionType && $step->stage === null
                : $step->stage !== null;
            if (! $valid || ($step->layer === LearningLayer::Tenant && $step->stage !== LearningStage::BeforeStart)) {
                throw ValidationException::withMessages(['stage' => __('learning.validation.stage')]);
            }
        });
    }

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByRaw("CASE stage WHEN 'before_start' THEN 0 WHEN 'first_months' THEN 1 WHEN 'whole_term' THEN 2 WHEN 'term_end' THEN 3 ELSE 4 END")->orderBy('order')->orderBy('id');
    }

    public function sourceFor(string $locale): ?LearningStepSource
    {
        return $this->sources->firstWhere('locale', $locale) ?? $this->sources->firstWhere('locale', 'lt') ?? $this->sources->first();
    }
}
