<?php

namespace Database\Factories;

use App\Models\PlanningResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanningResource>
 */
class PlanningResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_start' => fake()->numberBetween(2023, 2026),
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement(['file', 'url', 'text']),
            'content' => null,
            'category' => null,
            'sort_order' => 0,
        ];
    }

    public function url(): static
    {
        return $this->state(fn () => [
            'type' => 'url',
            'content' => fake()->url(),
        ]);
    }

    public function text(): static
    {
        return $this->state(fn () => [
            'type' => 'text',
            'content' => fake()->paragraphs(2, true),
        ]);
    }

    public function file(): static
    {
        return $this->state(fn () => [
            'type' => 'file',
            'content' => null,
        ]);
    }

    public function tipTemplate(): static
    {
        return $this->state(fn () => [
            'category' => 'tip_template',
            'title' => 'TĮP šablonas',
        ]);
    }

    public function mvpTemplate(): static
    {
        return $this->state(fn () => [
            'category' => 'mvp_template',
            'title' => 'MVP šablonas',
        ]);
    }
}
