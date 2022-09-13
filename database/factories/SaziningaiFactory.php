<?php

namespace Database\Factories;

use App\Models\Padalinys;
use App\Models\Saziningai;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class SaziningaiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Saziningai::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => bin2hex(random_bytes(15)),
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'exam_type' => Arr::random(['egzaminas', 'koliokviumas']),
            'padalinys' => Padalinys::inRandomOrder()->select('id')->first()->id,
            'place' => $this->faker->city(),
            'duration' => $this->faker->numberBetween(30, 90) . 'min',
            'subject_name' => $this->faker->word(),
            'exam_holders' => rand(30, 150),
            'students_need' => rand(1, 3),
        ];
    }
}
