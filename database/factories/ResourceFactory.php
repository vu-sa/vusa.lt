<?php

namespace Database\Factories;

use App\Models\Padalinys;
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
            'name' => $this->faker->word,
            'description' => $this->faker->paragraph,
            'location' => $this->faker->word,
            'capacity' => $this->faker->numberBetween(1, 10),
            'is_reservable' => $this->faker->boolean,
            'padalinys_id' => Padalinys::inRandomOrder()->first()->id,
        ];
    }
}
