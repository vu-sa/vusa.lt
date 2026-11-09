<?php

namespace Database\Factories;

use App\Models\Duty;
use App\Models\HandoverNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HandoverNote> */
class HandoverNoteFactory extends Factory
{
    protected $model = HandoverNote::class;

    public function definition(): array
    {
        return [
            'duty_id' => Duty::factory(),
            'user_id' => User::factory(),
            'dutiable_id' => null,
            'cadence_id' => null,
            'situation' => fake()->paragraph(),
            'open_issues' => null,
            'people' => null,
            'resources' => null,
            'advice' => null,
        ];
    }
}
