<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Initiative>
 */
class InitiativeFactory extends Factory
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
            'slug' => $this->faker->slug,
            'description' => $this->faker->text,
            'participation_url' => $this->faker->url,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'logo' => $this->faker->imageUrl,
            'cover' => $this->faker->imageUrl,
            'start_date' => $this->faker->date,
            'end_date' => $this->faker->date,
        ];
    }
}
