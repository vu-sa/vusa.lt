<?php

namespace Database\Factories;

use App\Models\StudySet;
use App\Models\StudySetCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudySetCourse>
 */
class StudySetCourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['lt' => $this->faker->sentence(4), 'en' => $this->faker->sentence(4)],
            'order' => $this->faker->numberBetween(1, 10),
            'semester' => $this->faker->randomElement(['autumn', 'spring']),
            'credits' => 5,
            'is_visible' => true,
            'study_set_id' => StudySet::factory(),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
