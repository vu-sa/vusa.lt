<?php

namespace Database\Factories;

use App\Models\PlanningActivity;
use App\Models\PlanningProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanningActivity>
 */
class PlanningActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'planning_process_id' => PlanningProcess::factory(),
            'name' => $this->faker->sentence(4),
            'month' => $this->faker->numberBetween(1, 12),
            'responsible_person' => $this->faker->name(),
            'level' => $this->faker->randomElement(['padalinio', 'strateginis', 'srities']),
            'order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
