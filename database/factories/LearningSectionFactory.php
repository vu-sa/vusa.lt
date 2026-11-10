<?php

namespace Database\Factories;

use App\Models\LearningSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningSection> */
class LearningSectionFactory extends Factory
{
    protected $model = LearningSection::class;

    public function definition(): array
    {
        return ['title' => ['lt' => 'Įvadiniai mokymai', 'en' => 'Introductory training'],
            'description' => ['lt' => 'Mokymų dokumentai', 'en' => 'Training documents'], 'order' => 0];
    }
}
