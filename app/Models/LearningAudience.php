<?php

namespace App\Models;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $audienceable_type
 * @property string $audienceable_id
 * @property LearningAudienceType $type
 * @property string|null $source_id
 * @property int|null $tenant_id
 * @property LearningImportance $importance
 * @property LearningStage|null $stage
 * @property array|string|null $description
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read \Illuminate\Database\Eloquent\Model $audienceable
 * @property-read Duty|null $duty
 * @property-read DutyType|null $dutyType
 * @property-read array $translatable_columns_from
 * @property-read Institution|null $institution
 * @property-read InstitutionType|null $institutionType
 * @property-read Role|null $purposeRole
 * @property-read Role|null $responsibility
 * @property-read Tenant|null $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\LearningAudienceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningAudience whereTranslationLike(array|string $columns, string $search, ?array $locales = null)
 *
 * @mixin \Eloquent
 */
#[Fillable(['audienceable_type', 'audienceable_id', 'type', 'source_id', 'tenant_id', 'importance', 'stage', 'description', 'order'])]
class LearningAudience extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['description'];

    public const LABEL_RELATIONS = ['tenant', 'dutyType', 'institutionType', 'responsibility', 'duty', 'institution', 'purposeRole'];

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function audienceable(): MorphTo
    {
        return $this->morphTo();
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
