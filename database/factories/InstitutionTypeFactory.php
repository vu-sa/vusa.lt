<?php

namespace Database\Factories;

use App\Enums\InstitutionScope;
use App\Models\InstitutionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionType>
 */
class InstitutionTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ['lt' => $this->faker->sentence, 'en' => $this->faker->sentence],
            'description' => ['lt' => $this->faker->paragraph, 'en' => $this->faker->paragraph],
            'slug' => $this->faker->slug,
        ];
    }

    /**
     * @param  InstitutionScope|null  $scope  null leaves the scope to be inherited.
     */
    public function withGovernanceScope(?InstitutionScope $scope = null): static
    {
        return $this->state(fn () => [
            'extra_attributes' => $scope === null ? null : ['governance_scope' => $scope->value],
        ]);
    }
}
