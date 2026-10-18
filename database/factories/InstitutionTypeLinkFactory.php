<?php

namespace Database\Factories;

use App\Enums\InstitutionRelationKind;
use App\Enums\InstitutionTypeLinkScope;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionTypeLink>
 */
class InstitutionTypeLinkFactory extends Factory
{
    protected $model = InstitutionTypeLink::class;

    public function definition(): array
    {
        return [
            'source_type_id' => InstitutionType::factory(),
            'target_type_id' => InstitutionType::factory(),
            'kind' => InstitutionRelationKind::Related,
            'mutual' => false,
            'scope' => InstitutionTypeLinkScope::SameTenant,
        ];
    }

    public function mutual(): static
    {
        return $this->state(['mutual' => true]);
    }

    public function crossTenant(): static
    {
        return $this->state(['scope' => InstitutionTypeLinkScope::MainToUnits]);
    }

    public function allTenants(): static
    {
        return $this->state(['scope' => InstitutionTypeLinkScope::AllTenants]);
    }
}
