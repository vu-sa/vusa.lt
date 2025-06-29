<?php

namespace Database\Factories;

use App\Enums\DegreeEnum;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudyProgram>
 */
class StudyProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $programNames = [
            'Informatikos',
            'Matematikos',
            'Fizikos',
            'Chemijos',
            'Biologijos',
            'Teisės',
            'Ekonomikos',
            'Psichologijos',
            'Filosofijos',
            'Istorijos',
        ];

        // Use DegreeEnum for consistent degree values
        $degree = $this->faker->randomElement(DegreeEnum::toValues());

        $degreeTranslations = [
            DegreeEnum::BA()->value => 'bakalauras',
            DegreeEnum::MA()->value => 'magistras',
            DegreeEnum::PHD()->value => 'daktaras',
            DegreeEnum::INTEGRATED_STUDIES()->value => 'vientisųjų studijų',
            DegreeEnum::PROFESSIONAL_PEDAGOGY()->value => 'profesinės pedagogikos',
            DegreeEnum::OTHER()->value => 'kitas',
        ];

        $programName = $this->faker->randomElement($programNames);
        $degreeTranslation = $degreeTranslations[$degree] ?? $degree;

        return [
            'name' => [
                'lt' => "{$programName} {$degreeTranslation}",
                'en' => $this->faker->words(2, true).' '.$degree,
            ],
            'degree' => $degree,
            'tenant_id' => Tenant::factory(),
        ];
    }

    /**
     * Create a study program with a specific degree.
     */
    public function withDegree(string $degree)
    {
        $degreeTranslations = [
            DegreeEnum::BA()->value => 'bakalauras',
            DegreeEnum::MA()->value => 'magistras',
            DegreeEnum::PHD()->value => 'daktaras',
            DegreeEnum::INTEGRATED_STUDIES()->value => 'vientisųjų studijų',
            DegreeEnum::PROFESSIONAL_PEDAGOGY()->value => 'profesinės pedagogikos',
            DegreeEnum::OTHER()->value => 'kitas',
        ];

        return $this->state(function (array $attributes) use ($degree, $degreeTranslations) {
            $programName = $this->faker->randomElement([
                'Informatikos', 'Matematikos', 'Fizikos', 'Teisės',
            ]);

            $degreeTranslation = $degreeTranslations[$degree] ?? $degree;

            return [
                'degree' => $degree,
                'name' => [
                    'lt' => "{$programName} {$degreeTranslation}",
                    'en' => $this->faker->words(2, true).' '.$degree,
                ],
            ];
        });
    }

    /**
     * Create a study program for a specific tenant.
     */
    public function forTenant(Tenant $tenant)
    {
        return $this->state(function (array $attributes) use ($tenant) {
            return [
                'tenant_id' => $tenant->id,
            ];
        });
    }
}
