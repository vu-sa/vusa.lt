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
        ];
    }
}
