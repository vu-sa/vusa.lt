<?php

namespace Database\Factories\Pivots;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pivots\Dutiable>
 */
class DutiableFactory extends Factory
{
    protected $model = Dutiable::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // 60% chance to get a study program
        $shouldHaveStudyProgram = $this->faker->boolean(60);
        $studyProgramId = null;

        if ($shouldHaveStudyProgram) {
            $studyProgramId = StudyProgram::inRandomOrder()->value('id');
            // If no study programs exist, create one
            if (! $studyProgramId) {
                $studyProgramId = StudyProgram::factory()->create()->id;
            }
        }

        return [
            'duty_id' => Duty::factory(),
            'dutiable_type' => User::class,
            'dutiable_id' => User::factory(),
            'start_date' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
            'end_date' => $this->faker->optional(0.3)->dateTimeBetween('-1 year', 'now'),
            'additional_email' => $this->faker->optional(0.2)->safeEmail(),
            'additional_photo' => $this->faker->optional(0.1)->imageUrl(200, 200, 'people'),
            'description' => $this->faker->optional(0.4)->randomElement([
                ['lt' => $this->faker->paragraph(1), 'en' => $this->faker->paragraph(1)],
                ['lt' => $this->faker->paragraph(1), 'en' => ''],
                ['lt' => $this->faker->paragraph(1)],
            ]),
            'study_program_id' => $studyProgramId,
            'use_original_duty_name' => $this->faker->boolean(20), // 20% chance true
        ];
    }

    /**
     * Create a dutiable for a specific user.
     */
    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'dutiable_type' => User::class,
                'dutiable_id' => $user->id,
            ];
        });
    }

    /**
     * Create a dutiable for a specific duty.
     */
    public function forDuty(Duty $duty)
    {
        return $this->state(function (array $attributes) use ($duty) {
            return [
                'duty_id' => $duty->id,
            ];
        });
    }

    /**
     * Create a dutiable with a specific study program.
     */
    public function withStudyProgram(?StudyProgram $studyProgram = null)
    {
        return $this->state(function (array $attributes) use ($studyProgram) {
            return [
                'study_program_id' => $studyProgram?->id ?? StudyProgram::inRandomOrder()->value('id') ?? StudyProgram::factory()->create()->id,
            ];
        });
    }

    /**
     * Configure to randomly assign study programs (60% chance).
     */
    public function withRandomStudyProgram()
    {
        return $this->state(function (array $attributes) {
            return [
                'study_program_id' => $this->faker->optional(0.6)->randomElement([
                    StudyProgram::inRandomOrder()->value('id'),
                    null,
                ]),
            ];
        });
    }

    /**
     * Create an active dutiable (no end date).
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'end_date' => null,
            ];
        });
    }

    /**
     * Create an ended dutiable.
     */
    public function ended()
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-2 years', '-6 months');

            return [
                'start_date' => $startDate,
                'end_date' => $this->faker->dateTimeBetween($startDate, 'now'),
            ];
        });
    }
}
