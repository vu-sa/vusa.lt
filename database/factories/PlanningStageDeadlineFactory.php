<?php

namespace Database\Factories;

use App\Models\PlanningStageDeadline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanningStageDeadline>
 */
class PlanningStageDeadlineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-1 year', '+6 months');

        return [
            'academic_year_start' => $this->faker->numberBetween(2023, 2026),
            'stage' => $this->faker->numberBetween(1, 5),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+4 weeks'),
        ];
    }
}
