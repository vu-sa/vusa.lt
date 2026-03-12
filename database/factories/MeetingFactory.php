<?php

namespace Database\Factories;

use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $start_time = fake()->dateTime();

        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'start_time' => $start_time,
            // end time after start_time
            'end_time' => Carbon::instance($start_time)->addHours(rand(1, 3)),
        ];
    }
}
