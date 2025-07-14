<?php

namespace Database\Factories;

use App\Models\Navigation;
use Illuminate\Database\Eloquent\Factories\Factory;

class NavigationFactory extends Factory
{
    protected $model = Navigation::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'lang' => 'lt',
            'url' => '#',
            'parent_id' => 0,
            'order' => 1,
            'is_active' => true,
            'extra_attributes' => [],
        ];
    }
}
