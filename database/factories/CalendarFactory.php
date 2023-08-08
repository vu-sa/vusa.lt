<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\Padalinys;
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
            'date' => fake()->dateTimeBetween('-1 years', '+1 years'),
            'title' => fake()->sentence,
            'description' => fake()->paragraph,
            'category' => Arr::random(['red', 'yellow', 'grey']),
            'url' => fake()->url,
            'padalinys_id' => Padalinys::query()->inRandomOrder()->first()->id,
        ];
    }
}
