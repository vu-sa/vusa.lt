<?php

namespace Database\Factories;

use App\Enums\LearningAudienceType;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\LearningAudience;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeItem> */
class KnowledgeItemFactory extends Factory
{
    protected $model = KnowledgeItem::class;

    public function definition(): array
    {
        return [
            'title' => ['lt' => fake()->sentence(4), 'en' => fake()->sentence(4)],
            'description' => ['lt' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'per_term' => false, 'order' => 0, 'is_active' => true,
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
        return $this->withoutAfterCreating()->afterCreating(function (KnowledgeItem $step) use ($attributes): void {
            LearningAudience::factory()->for($step, 'audienceable')->create([...$attributes, 'stage' => null]);
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
}
