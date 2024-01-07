<?php

namespace Database\Factories;

use App\Models\Doing;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doing>
 */
class DoingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'date' => $this->faker->dateTime,
        ];
    }

    public function withType()
    {
        return $this->afterCreating(function ($doing) {
            $doing->types()->attach(Type::query()->where('model_type', Doing::class)->inRandomOrder()->first());
        });
    }
}
