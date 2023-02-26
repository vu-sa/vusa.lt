<?php

namespace Database\Factories;

use App\Models\Calendar;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class CalendarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Calendar::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'date' => $this->faker->dateTimeBetween('-10 weeks'),
            'title' => $this->faker->paragraph(1),
            'description' => $this->faker->paragraph(2),
            'category' => Arr::random(['red', 'yellow', 'grey']),
            'url' => null,
            'padalinys_id' => 16,
        ];
    }
}
