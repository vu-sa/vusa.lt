<?php

namespace Database\Factories;

use App\Models\Problem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Problem>
 */
class ProblemFactory extends Factory
{
    protected $model = Problem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => [
                'lt' => $this->faker->sentence(4),
                'en' => $this->faker->sentence(4),
            ],
            'description' => [
                'lt' => $this->faker->paragraph(),
                'en' => $this->faker->paragraph(),
            ],
            'tenant_id' => Tenant::factory(),
            'created_by' => User::factory(),
            'occurred_at' => $this->faker->date(),
            'status' => 'open',
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => 'resolved',
            'resolved_at' => $this->faker->date(),
        ]);
    }
}
