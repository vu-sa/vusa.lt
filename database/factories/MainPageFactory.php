<?php

namespace Database\Factories;

use App\Models\MainPage;
use App\Models\Padalinys;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class MainPageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MainPage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'link' => $this->faker->url(),
            'position' => "",
            'text' => $this->faker->word(),
            'padalinys_id' => Padalinys::inRandomOrder()->select('id')->first()->id,
            'lang' => Arr::random(['lt', 'en']),
        ];
    }
}
