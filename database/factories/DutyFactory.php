<?php

namespace Database\Factories;

use App\Models\Duty;
use App\Models\DutyInstitution;
use App\Models\DutyType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DutyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Duty::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->jobTitle(),
            // generate html description
            'description' => '<p>' . $this->faker->paragraph(1) . '</p><p>' . $this->faker->paragraph(1) . '</p>',
            'type_id' => DutyType::inRandomOrder()->select('id')->first()->id,
            'institution_id' => DutyInstitution::inRandomOrder()->select('id')->first()->id,
            'email' => $this->faker->safeEmail(),
        ];
    }
}
