<?php

namespace Database\Factories;

use App\Models\Padalinys;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DutyInstitution>
 */
class DutyInstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'short_name' => $this->faker->companySuffix(),
            'alias' => $this->faker->companySuffix(),
            // html descriptioj
            'description' => '<p>' . $this->faker->paragraph(1) . '</p><p>' . $this->faker->paragraph(1) . '</p>',
            'image_url' => $this->faker->imageUrl(640, 480, 'business', true),
            'type_id' => 1,
            'padalinys_id' => Padalinys::inRandomOrder()->select('id')->first()->id,
        ];
    }
}
