<?php

namespace Database\Factories;

use App\Models\SaziningaiExamObserver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class SaziningaiExamObserverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SaziningaiExamObserver::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'exam_uuid' => DB::table('saziningai_exams')->inRandomOrder()->select('uuid')->first()->uuid,
            'name' => $this->faker->name(),
            'padalinys_id' => DB::table('padaliniai')->inRandomOrder()->select('id')->first()->id,
            'phone' => $this->faker->phoneNumber(),
            'flow' => DB::table('saziningai_exam_flows')->inRandomOrder()->select('id')->first()->id,
            'has_arrived' => 'atvyko',
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
