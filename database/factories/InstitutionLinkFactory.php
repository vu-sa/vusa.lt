<?php

namespace Database\Factories;

use App\Enums\InstitutionRelationKind;
use App\Models\Institution;
use App\Models\InstitutionLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionLink>
 */
class InstitutionLinkFactory extends Factory
{
    protected $model = InstitutionLink::class;

    public function definition(): array
    {
        return [
            'source_institution_id' => Institution::factory(),
            'target_institution_id' => Institution::factory(),
            'kind' => InstitutionRelationKind::Related,
            'mutual' => false,
        ];
    }

    public function mutual(): static
    {
        return $this->state(['mutual' => true]);
    }
}
