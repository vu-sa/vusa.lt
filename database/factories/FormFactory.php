<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Form>
 */
class FormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['lt' => fake()->sentence, 'en' => fake()->sentence],
            'description' => ['lt' => fake()->paragraph, 'en' => fake()->paragraph],
            'user_id' => null,
            'tenant_id' => Tenant::factory(),
            'path' => ['lt' => fake()->slug, 'en' => fake()->slug],
            'publish_time' => fake()->optional(0.8)->dateTimeBetween('-1 month', '+1 month'),
        ];
    }

    /**
     * Mark the form as published (visible).
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'publish_time' => now()->subHour(),
        ]);
    }

    /**
     * Mark the form as unpublished (not yet visible).
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'publish_time' => now()->addHour(),
        ]);
    }
}
