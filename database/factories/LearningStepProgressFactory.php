<?php

namespace Database\Factories;

use App\Enums\LearningProgressStatus;
use App\Models\LearningStep;
use App\Models\LearningStepProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningStepProgress> */
class LearningStepProgressFactory extends Factory
{
    protected $model = LearningStepProgress::class;

    public function definition(): array
    {
        return ['learning_step_id' => LearningStep::factory(), 'user_id' => User::factory(), 'status' => LearningProgressStatus::Done, 'completed_at' => now()];
    }
}
