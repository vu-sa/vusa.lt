<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class SaziningaiExamFlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'exam_uuid' => DB::table('saziningai_exams')->inRandomOrder()->select('uuid')->first()->uuid,
            'start_time' => $this->faker->dateTimeBetween('now', '+10 weeks'),
        ];
    }
}
