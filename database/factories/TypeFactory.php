<?php

namespace Database\Factories;

use App\Enums\InstitutionScope;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Type;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Type>
 */
class TypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => ['lt' => $this->faker->sentence, 'en' => $this->faker->sentence],
            'model_type' => fake()->randomElement([Duty::class, Institution::class]),
            'description' => ['lt' => $this->faker->paragraph, 'en' => $this->faker->paragraph],
            'slug' => $this->faker->slug,
        ];
    }

    /**
     * @param  InstitutionScope|null  $scope  null leaves the scope to be inherited.
     */
    public function forInstitutions(?InstitutionScope $scope = null): static
    {
        return $this->state(fn () => [
            'model_type' => MorphMap::alias(Institution::class),
            'extra_attributes' => $scope === null ? null : ['governance_scope' => $scope->value],
        ]);
    }
}
