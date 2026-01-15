<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalFlow>
 */
class ApprovalFlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'flowable_type' => null,
            'flowable_id' => null,
            'steps' => [
                ['permission' => 'resources.update.padalinys', 'required_count' => 1],
            ],
            'is_sequential' => true,
            'escalation_days' => null,
        ];
    }

    /**
     * Global approval flow (not attached to specific models).
     */
    public function global(): static
    {
        return $this->state([
            'flowable_type' => null,
            'flowable_id' => null,
        ]);
    }

    /**
     * Multi-step approval flow.
     */
    public function multiStep(int $steps = 2): static
    {
        $stepConfigs = [];
        for ($i = 0; $i < $steps; $i++) {
            $stepConfigs[] = [
                'permission' => 'resources.update.padalinys',
                'required_count' => 1,
            ];
        }

        return $this->state(['steps' => $stepConfigs]);
    }

    /**
     * Approval flow with escalation.
     */
    public function withEscalation(int $days = 3): static
    {
        return $this->state(['escalation_days' => $days]);
    }
}
