<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'taskable_type' => Institution::class,
            'taskable_id' => Institution::query()->inRandomOrder()->first()?->id ?? Institution::factory(),
            'completed_at' => null,
        ];
    }

    /**
     * Task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
        ]);
    }

    /**
     * Task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays($this->faker->numberBetween(1, 10)),
            'completed_at' => null,
        ]);
    }

    /**
     * Task is due soon (within 7 days).
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->addDays($this->faker->numberBetween(1, 7)),
            'completed_at' => null,
        ]);
    }
}
