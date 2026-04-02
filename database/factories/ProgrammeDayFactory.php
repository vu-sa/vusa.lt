<?php

namespace Database\Factories;

use App\Models\Programme;
use App\Models\ProgrammeDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgrammeDay>
 */
class ProgrammeDayFactory extends Factory
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
            'start_time' => $this->faker->dateTime(),
            'programme_id' => Programme::factory(),
        ];
    }
}
