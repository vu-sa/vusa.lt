<?php

namespace Database\Factories;

use App\Models\ProgrammeBlock;
use App\Models\ProgrammeSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgrammeBlock>
 */
class ProgrammeBlockFactory extends Factory
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
            'description' => $this->faker->paragraph,
            'programme_section_id' => ProgrammeSection::factory(),
        ];
    }
}
