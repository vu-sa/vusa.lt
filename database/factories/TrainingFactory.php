<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Training>
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
            'organizer_id' => \App\Models\User::factory(),
            'institution_id' => \App\Models\Institution::factory(),
            'form_id' => \App\Models\Form::factory(),
            'max_participants' => $this->faker->numberBetween(1, 100),
            'is_online' => $this->faker->boolean,
            'is_hybrid' => $this->faker->boolean,
        ];
    }
}
