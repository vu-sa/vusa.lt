<?php

namespace Database\Factories;

use App\Models\LearningStep;
use App\Models\LearningStepSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningStepSource> */
class LearningStepSourceFactory extends Factory
{
    protected $model = LearningStepSource::class;

    public function definition(): array
    {
        return ['learning_step_id' => LearningStep::factory(), 'locale' => 'lt', 'url' => fake()->url(), 'document_id' => null];
    }
}
