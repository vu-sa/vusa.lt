<?php

namespace Database\Factories;

use App\Models\ProgrammePart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgrammePart>
 */
class ProgrammePartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'start_time' => $this->faker->time(),
            'duration' => $this->faker->randomNumber(2),
        ];
    }
}
