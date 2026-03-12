<?php

namespace Database\Factories;

use App\Models\Banner;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'image_url' => fake()->imageUrl(),
            'link_url' => fake()->url(),
            'lang' => fake()->randomElement(['lt', 'en']),
            'order' => fake()->unique()->numberBetween(1, 10000),
            'is_active' => fake()->boolean(),
            'tenant_id' => Tenant::factory(),
        ];
    }
}
