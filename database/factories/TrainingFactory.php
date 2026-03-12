<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Institution;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['lt' => $this->faker->sentence, 'en' => $this->faker->sentence],
            'description' => ['lt' => $this->faker->paragraph, 'en' => $this->faker->paragraph],
            'address' => $this->faker->address,
            'meeting_url' => $this->faker->url,
            'image' => $this->faker->imageUrl(),
            'status' => 'draft',
            'start_time' => $this->faker->dateTimeThisYear,
            'end_time' => $this->faker->dateTimeThisYear,
            'organizer_id' => User::factory(),
            'institution_id' => Institution::factory(),
            'form_id' => Form::factory(),
            'max_participants' => $this->faker->numberBetween(1, 100),
        ];
    }
}
