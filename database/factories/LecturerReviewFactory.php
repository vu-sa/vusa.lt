<?php

namespace Database\Factories;

use App\Models\LecturerReview;
use App\Models\StudySetCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LecturerReview>
 */
class LecturerReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lecturer' => ['lt' => $this->faker->name(), 'en' => $this->faker->name()],
            'comment' => ['lt' => $this->faker->paragraph(), 'en' => $this->faker->paragraph()],
            'is_visible' => true,
            'study_set_course_id' => StudySetCourse::factory(),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
