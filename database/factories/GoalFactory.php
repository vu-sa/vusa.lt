<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => [
                'lt' => $this->faker->sentence(4),
                'en' => $this->faker->sentence(4),
            ],
            'description' => [
                'lt' => '<p>'.$this->faker->paragraph().'</p>',
                'en' => '<p>'.$this->faker->paragraph().'</p>',
            ],
            'expected_result' => [
                'lt' => $this->faker->sentence(),
                'en' => $this->faker->sentence(),
            ],
            'status' => GoalStatus::Planned,
            'is_public' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (): array => ['is_public' => true]);
    }
}
