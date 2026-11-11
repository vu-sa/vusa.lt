<?php

namespace Database\Factories;

use App\Enums\LearningAudienceType;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\LearningAudience;
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
            'kind' => LearningStepKind::Action,
            'title' => ['lt' => fake()->sentence(4), 'en' => fake()->sentence(4)],
            'description' => ['lt' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'is_system' => false, 'order' => 0, 'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->audience([]);
    }

    public function withoutAudiences(): static
    {
        return $this->withoutAfterCreating();
    }

    public function audience(array $attributes): static
    {
        return $this->withoutAfterCreating()->afterCreating(function (LearningStep $step) use ($attributes): void {
            // Only actions sit on the term timeline.
            $stage = $step->kind === LearningStepKind::Action ? LearningStage::BeforeStart : null;
            LearningAudience::factory()->for($step, 'audienceable')->create(['stage' => $stage, ...$attributes]);
        });
    }

    public function system(string $key): static
    {
        $definition = SystemStepRegistry::definitions()[$key];
        $factory = $this->withoutAudiences()->state([
            'key' => $key, 'is_system' => true, 'kind' => LearningStepKind::Action,
            'title' => null, 'description' => null,
        ]);

        return $factory->afterCreating(function (LearningStep $step) use ($definition): void {
            $sources = $definition['audience'] === LearningAudienceType::Everyone ? [null]
                : [ResponsibilityPurpose::StudentRepresentation->value, ResponsibilityPurpose::StudentRepCoordination->value];
            foreach ($sources as $source) {
                LearningAudience::factory()->for($step, 'audienceable')->create([
                    'type' => $source === null ? LearningAudienceType::Everyone : LearningAudienceType::Purpose,
                    'source_id' => $source, 'stage' => $definition['stage'], 'importance' => $definition['importance'],
                ]);
            }
        });
    }

    public function forInstitutionType(?InstitutionType $type = null): static
    {
        return $this->audience(['type' => LearningAudienceType::InstitutionType, 'source_id' => $type?->id ?? InstitutionType::factory()]);
    }

    public function forDutyType(?DutyType $type = null): static
    {
        return $this->audience(['type' => LearningAudienceType::DutyType, 'source_id' => $type?->id ?? DutyType::factory()]);
    }

    public function forTenant(?Tenant $tenant = null): static
    {
        return $this->audience(['type' => LearningAudienceType::Everyone, 'tenant_id' => $tenant?->id ?? Tenant::factory()]);
    }

    public function meetingPrep(?InstitutionType $type = null): static
    {
        return $this->audience(['type' => LearningAudienceType::InstitutionType, 'source_id' => $type?->id ?? InstitutionType::factory(), 'stage' => null])->state(['kind' => LearningStepKind::MeetingPrep]);
    }
}
