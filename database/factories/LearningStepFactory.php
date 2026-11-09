<?php

namespace Database\Factories;

use App\Enums\LearningImportance;
use App\Enums\LearningLayer;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\Tenant;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningStep> */
class LearningStepFactory extends Factory
{
    protected $model = LearningStep::class;

    public function definition(): array
    {
        return [
            'kind' => LearningStepKind::Knowledge, 'layer' => LearningLayer::Platform,
            'stage' => LearningStage::BeforeStart, 'importance' => LearningImportance::Recommended,
            'title' => ['lt' => fake()->sentence(4), 'en' => fake()->sentence(4)],
            'description' => ['lt' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'is_system' => false, 'per_term' => false, 'order' => 0, 'is_active' => true,
        ];
    }

    public function system(string $key): static
    {
        $definition = SystemStepRegistry::definitions()[$key];

        return $this->state([
            'key' => $key, 'is_system' => true, 'kind' => LearningStepKind::Action,
            'layer' => $definition['layer'], 'stage' => $definition['stage'],
            'importance' => $definition['importance'], 'per_term' => $definition['per_term'],
            'title' => null, 'description' => null,
        ]);
    }

    public function forInstitutionType(?InstitutionType $type = null): static
    {
        return $this->state(['layer' => LearningLayer::InstitutionType, 'institution_type_id' => $type?->id ?? InstitutionType::factory()]);
    }

    public function forDutyType(?DutyType $type = null): static
    {
        return $this->state(['layer' => LearningLayer::DutyType, 'duty_type_id' => $type?->id ?? DutyType::factory()]);
    }

    public function forTenant(?Tenant $tenant = null): static
    {
        return $this->state(['layer' => LearningLayer::Tenant, 'tenant_id' => $tenant?->id ?? Tenant::factory(), 'stage' => LearningStage::BeforeStart]);
    }

    public function meetingPrep(?InstitutionType $type = null): static
    {
        return $this->forInstitutionType($type)->state(['kind' => LearningStepKind::MeetingPrep, 'stage' => null]);
    }
}
