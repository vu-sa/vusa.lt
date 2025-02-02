<?php

namespace Database\Factories;

use App\Models\QuickLink;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class QuickLinkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QuickLink::class;

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
