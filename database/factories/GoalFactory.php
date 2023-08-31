<?php

namespace Database\Factories;

use App\Models\Padalinys;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Goal>
 */
class GoalFactory extends Factory
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
            'description' => $this->faker->text,
            'start_date' => $this->faker->date,
            // end date later than start date
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'padalinys_id' => Padalinys::factory(),
        ];
    }
}
