<?php

namespace Database\Factories;

use Illuminate\Support\Facades\DB;
use App\Models\Saziningai_people;
use Illuminate\Database\Eloquent\Factories\Factory;

class Saziningai_peopleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Saziningai_people::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'exam_uuid' => DB::table('saziningai_exams')->inRandomOrder()->select('uuid')->first()->uuid,
            'name_p' => $this->faker->name(),
            'padalinys_p' => 'if',
            'contact_p' => $this->faker->phoneNumber(),
            'flow' => rand(1,3),
            'status_p' => 'atvyko',
        ];
    }
}
