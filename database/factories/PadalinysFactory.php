<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Padalinys>
 */
class PadalinysFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'short_name' => $this->faker->companySuffix(),
            'alias' => $this->faker->companySuffix(),
            'description' => '<p>'.$this->faker->paragraph(1).'</p><p>'.$this->faker->paragraph(1).'</p>',
        ];
    }
}
