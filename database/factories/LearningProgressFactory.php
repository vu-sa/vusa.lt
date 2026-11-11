<?php

namespace Database\Factories;

use App\Enums\LearningProgressStatus;
use App\Models\LearningProgress;
use App\Models\LearningStep;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningProgress> */
class LearningProgressFactory extends Factory
{
    protected $model = LearningProgress::class;

    public function definition(): array
    {
        return ['progressable_type' => MorphMap::alias(LearningStep::class), 'progressable_id' => LearningStep::factory(), 'user_id' => User::factory(), 'status' => LearningProgressStatus::Done, 'completed_at' => now()];
    }
}
