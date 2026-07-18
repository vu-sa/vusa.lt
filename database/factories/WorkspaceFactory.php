<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'institution_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function withInstitution(): static
    {
        return $this->state(fn (): array => [
            'institution_id' => Institution::factory(),
        ]);
    }
}
