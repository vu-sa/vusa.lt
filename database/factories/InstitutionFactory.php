<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Institution>
 */
class InstitutionFactory extends Factory
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
            'description' => '<p>'.$this->faker->paragraph(1).'</p><p>'.$this->faker->paragraph(1).'</p>',
            // sometimes image is not available
            'image_url' => $this->faker->boolean(50) ? $this->faker->imageUrl() : null,
            'padalinys_id' => Padalinys::factory(),
        ];
    }

    public function withType()
    {
        return $this->afterCreating(function ($institution) {
            $institution->types()->attach(Type::query()->where('model_type', Institution::class)->inRandomOrder()->first());
        });
    }
}
