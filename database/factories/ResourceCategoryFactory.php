<?php

namespace Database\Factories;

use App\Models\ResourceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceCategory>
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
