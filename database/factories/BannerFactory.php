<?php

namespace Database\Factories;

use App\Models\Padalinys;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingMatter>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => fake()->sentence(3),
            'image_url' => fake()->imageUrl(),
            'link_url' => fake()->url(),
            'lang' => fake()->randomElement(['lt', 'en']),
            'order' => fake()->numberBetween(1, 1000),
            'is_active' => fake()->boolean(),
            'padalinys_id' => Padalinys::factory(),
        ];
    }
}
