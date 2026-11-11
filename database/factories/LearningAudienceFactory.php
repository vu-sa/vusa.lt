<?php

namespace Database\Factories;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\LearningAudience;
use App\Models\LearningStep;
use App\Models\Role;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningAudience> */
class LearningAudienceFactory extends Factory
{
    protected $model = LearningAudience::class;

    public function definition(): array
    {
        return ['audienceable_type' => MorphMap::alias(LearningStep::class), 'audienceable_id' => LearningStep::factory()->withoutAudiences(), 'type' => LearningAudienceType::Everyone,
            'importance' => LearningImportance::Recommended, 'stage' => LearningStage::BeforeStart,
            'description' => ['lt' => '', 'en' => ''], 'order' => 0];
    }

    public function everyone(): static
    {
        return $this->state(['type' => LearningAudienceType::Everyone, 'source_id' => null]);
    }

    public function dutyType(?DutyType $type = null): static
    {
        return $this->state(['type' => LearningAudienceType::DutyType, 'source_id' => $type?->id ?? DutyType::factory()]);
    }

    public function institutionType(?InstitutionType $type = null): static
    {
        return $this->state(['type' => LearningAudienceType::InstitutionType, 'source_id' => $type?->id ?? InstitutionType::factory()]);
    }

    public function responsibility(?Role $role = null): static
    {
        return $this->state(['type' => LearningAudienceType::Responsibility, 'source_id' => $role?->id ?? Role::factory()]);
    }

    public function purpose(ResponsibilityPurpose $purpose = ResponsibilityPurpose::StudentRepresentation): static
    {
        return $this->state(['type' => LearningAudienceType::Purpose, 'source_id' => $purpose->value]);
    }

    public function duty(?Duty $duty = null): static
    {
        return $this->state(['type' => LearningAudienceType::Duty, 'source_id' => $duty?->id ?? Duty::factory()]);
    }

    public function institution(?Institution $institution = null): static
    {
        return $this->state(['type' => LearningAudienceType::Institution, 'source_id' => $institution?->id ?? Institution::factory()]);
    }
}
