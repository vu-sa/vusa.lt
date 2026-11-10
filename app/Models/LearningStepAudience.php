<?php

namespace App\Models;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $learning_step_id
 * @property-read LearningStep $step
 * @property-read Tenant|null $tenant
 * @property-read DutyType|null $dutyType
 * @property-read InstitutionType|null $institutionType
 * @property-read Role|null $responsibility
 * @property-read Duty|null $duty
 * @property-read Institution|null $institution
 * @property-read Role|null $purposeRole
 * @property string $id
 * @property LearningAudienceType $type
 * @property string|null $source_id
 * @property int|null $tenant_id
 * @property LearningImportance $importance
 * @property LearningStage|null $stage
 * @property array|string|null $description
 * @property int $order
 */
#[Fillable(['learning_step_id', 'type', 'source_id', 'tenant_id', 'importance', 'stage', 'description', 'order'])]
class LearningStepAudience extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['description'];

    public const LABEL_RELATIONS = ['tenant', 'dutyType', 'institutionType', 'responsibility', 'duty', 'institution', 'purposeRole'];

    /** @return BelongsTo<LearningStep, $this> */
    public function step(): BelongsTo
    {
        return $this->belongsTo(LearningStep::class, 'learning_step_id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<DutyType, $this> */
    public function dutyType(): BelongsTo
    {
        return $this->belongsTo(DutyType::class, 'source_id');
    }

    /** @return BelongsTo<InstitutionType, $this> */
    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class, 'source_id');
    }

    /** @return BelongsTo<Role, $this> */
    public function responsibility(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'source_id');
    }

    /** @return BelongsTo<Duty, $this> */
    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class, 'source_id');
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'source_id');
    }

    /** @return BelongsTo<Role, $this> */
    public function purposeRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'source_id', 'purpose');
    }

    public function label(string $locale): string
    {
        $label = match ($this->type) {
            LearningAudienceType::Everyone => (string) __('learning.audience_types.everyone', [], $locale),
            LearningAudienceType::DutyType => (string) $this->dutyType?->getTranslation('title', $locale),
            LearningAudienceType::InstitutionType => (string) $this->institutionType?->getTranslation('title', $locale),
            LearningAudienceType::Responsibility => $this->responsibility?->displayTitle($locale) ?? '',
            LearningAudienceType::Duty => (string) $this->duty?->getTranslation('name', $locale),
            LearningAudienceType::Institution => (string) $this->institution?->getTranslation('name', $locale),
            LearningAudienceType::Purpose => $this->purposeRole?->displayTitle($locale)
                ?? (string) __(ResponsibilityPurpose::from($this->source_id)->labelKey(), [], $locale),
        };

        return $label.($this->tenant_id !== null ? ' · '.$this->tenant?->shortname : '');
    }

    protected function casts(): array
    {
        return ['type' => LearningAudienceType::class, 'importance' => LearningImportance::class, 'stage' => LearningStage::class, 'order' => 'integer'];
    }
}
