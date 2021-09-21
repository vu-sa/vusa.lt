<?php

namespace Database\Factories;

use App\Models\Agenda;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgendaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Agenda::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'date' => $this->faker->dateTimeBetween('-10 weeks')->format('Y-m-d'),
            'title' => $this->faker->paragraph(1),
            'description' => $this->faker->paragraph(2),
            'classname' => 'red',
            'owner' => 0,
            'editor' => 1,
            // 'editor_time' => now(),
        ];
    }
}
