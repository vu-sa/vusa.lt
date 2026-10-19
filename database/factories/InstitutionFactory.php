<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Institution>
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
            // companySuffix() has only a few values, so seeded institutions collided with test ones.
            'alias' => Str::slug($this->faker->words(2, true)).'-'.$this->faker->numberBetween(1000, 999999),
            // html descriptioj
            'description' => '<p>'.$this->faker->paragraph(2).'</p><p>'.$this->faker->paragraph(2).'</p><p>'.$this->faker->paragraph(2).'</p>',
            'tenant_id' => Tenant::factory(),
        ];
    }

    public function withType()
    {
        return $this->afterCreating(function ($institution) {
            $institution->types()->attach(InstitutionType::query()->inRandomOrder()->first());
        });
    }
}
