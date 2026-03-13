<?php

namespace Database\Factories;

use App\Models\PlanningProcess;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanningProcess>
 */
class PlanningProcessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->inRandomOrder()->first()?->id ?? Tenant::factory(),
            'academic_year_start' => $this->faker->numberBetween(2023, 2026),
            'moderator_user_id' => null,
            'current_stage' => 1,
            'expectations_text' => null,
            'expectations_submitted_at' => null,
            'meeting_1_notes' => null,
            'meeting_1_date' => null,
            'meeting_2_notes' => null,
            'meeting_2_date' => null,
            'selected_problem_id' => null,
            'goal_text' => null,
            'goal_approved_at' => null,
            'tip_approved_at' => null,
            'tip_approved_by' => null,
            'mvp_approved_at' => null,
            'mvp_approved_by' => null,
            'reflection_text' => null,
            'reflection_submitted_at' => null,
            'locked_at' => null,
        ];
    }

    /**
     * Process is in Stage I with expectations submitted.
     */
    public function withExpectations(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 1,
            'expectations_text' => $this->faker->paragraph(),
            'expectations_submitted_at' => now(),
        ]);
    }

    /**
     * Process has an approved goal (Stage II complete).
     */
    public function withApprovedGoal(): static
    {
        return $this->withExpectations()->state(fn (array $attributes) => [
            'current_stage' => 2,
            'meeting_1_notes' => $this->faker->paragraph(),
            'meeting_1_date' => now()->subWeeks(4),
            'meeting_2_notes' => $this->faker->paragraph(),
            'meeting_2_date' => now()->subWeeks(2),
            'goal_text' => $this->faker->sentence(),
            'goal_approved_at' => now(),
        ]);
    }

    /**
     * Process is locked/archived.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_at' => now(),
        ]);
    }
}
