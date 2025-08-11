<?php

namespace Database\Factories;

use App\Models\Duty;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Type>
 */
class TypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => ['lt' => $this->faker->sentence, 'en' => $this->faker->sentence],
            'model_type' => fake()->randomElement([Duty::class, Institution::class]),
            'description' => ['lt' => $this->faker->paragraph, 'en' => $this->faker->paragraph],
            'slug' => $this->faker->slug,
        ];
    }
}
