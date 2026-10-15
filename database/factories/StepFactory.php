<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\Step;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Step>
 */
class StepFactory extends Factory
{
    protected $model = Step::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory(),
            'title' => [
                'lt' => $this->faker->sentence(5),
                'en' => $this->faker->sentence(5),
            ],
            'happened_on' => $this->faker->date(),
        ];
    }
}
