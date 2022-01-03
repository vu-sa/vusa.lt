<?php

namespace Database\Factories;

use App\Models\Saziningai;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'contact' => $this->faker->phoneNumber(),
            'exam' => 'koliokviumas',
            'padalinys' => 'gmc',
            'place' => 'ITPC',
            'time' => $this->faker->dateTimeBetween('+1 week', '+3 weeks')->format('Y-m-d H:i:s') . ' | ' . $this->faker->dateTimeBetween('+1 week', '+3 weeks')->format('Y-m-d H:i:s') . ' | ' . $this->faker->dateTimeBetween('+1 week', '+3 weeks')->format('Y-m-d H:i:s') . ' | ',
            'duration' => $this->faker->numberBetween(30, 90),
            'subject_name' => $this->faker->word(),
            'count' => rand(30, 150),
        ];
    }
}
