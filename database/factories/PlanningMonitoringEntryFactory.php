<?php

namespace Database\Factories;

use App\Models\PlanningMonitoringEntry;
use App\Models\PlanningProcess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanningMonitoringEntry>
 */
class PlanningMonitoringEntryFactory extends Factory
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
            'quarter' => $this->faker->numberBetween(1, 4),
            'status_text' => $this->faker->paragraph(),
            'submitted_by' => User::query()->inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }
}
