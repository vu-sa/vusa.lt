<?php

namespace Database\Factories;

use App\Models\MainPage;
use App\Models\Tenant;
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
            'text' => $this->faker->word(),
            'tenant_id' => Tenant::factory(),
            'lang' => Arr::random(['lt', 'en']),
        ];
    }
}
