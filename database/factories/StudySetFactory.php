<?php

namespace Database\Factories;

use App\Models\StudySet;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudySet>
 */
class StudySetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['lt' => $this->faker->words(3, true), 'en' => $this->faker->words(3, true)],
            'description' => ['lt' => $this->faker->sentences(2, true), 'en' => $this->faker->sentences(2, true)],
            'order' => $this->faker->numberBetween(1, 10),
            'is_visible' => true,
            'tenant_id' => Tenant::factory(),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
