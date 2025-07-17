<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResourceCategory>
 */
class ResourceCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'lt' => $this->faker->words(2, true),
                'en' => $this->faker->words(2, true),
            ],
            'description' => [
                'lt' => $this->faker->sentence(),
                'en' => $this->faker->sentence(),
            ],
            'icon' => $this->faker->randomElement(['building', 'car', 'computer', 'room', 'tool']),
        ];
    }
}
