<?php

namespace Database\Factories;

use App\Models\Doing;
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
            'title' => $this->faker->sentence,
            'model_type' => fake()->randomElement([Doing::class, Duty::class, Institution::class]),
            'description' => $this->faker->paragraph,
            'slug' => $this->faker->slug,
        ];
    }
}
