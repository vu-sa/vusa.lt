<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'lt' => $this->faker->words(2, true),
                'en' => $this->faker->words(2, true),
            ],
            'description' => [
                'lt' => $this->faker->paragraph(),
                'en' => $this->faker->paragraph(),
            ],
            'location' => $this->faker->address,
            'capacity' => $this->faker->numberBetween(1, 10),
            'is_reservable' => $this->faker->boolean,
            'tenant_id' => Tenant::factory(),
        ];
    }
}
