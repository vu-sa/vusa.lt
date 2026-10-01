<?php

namespace Database\Factories;

use App\Enums\InstitutionScope;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\DutyType;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DutyType>
 */
class DutyTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => ['lt' => $this->faker->sentence, 'en' => $this->faker->sentence],
            'description' => ['lt' => $this->faker->paragraph, 'en' => $this->faker->paragraph],
            'slug' => $this->faker->slug,
        ];
    }

}
