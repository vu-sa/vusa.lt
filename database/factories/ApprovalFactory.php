<?php

namespace Database\Factories;

use App\Enums\ApprovalDecision;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Approval>
 */
class ApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'approvable_type' => Reservation::class,
            'approvable_id' => Reservation::factory(),
            'decision' => null,
            'step' => 1,
            'notes' => null,
        ];
    }

    /**
     * Approval with approved decision.
     */
    public function approved(): static
    {
        return $this->state(['decision' => ApprovalDecision::Approved]);
    }

    /**
     * Approval with rejected decision.
     */
    public function rejected(): static
    {
        return $this->state(['decision' => ApprovalDecision::Rejected]);
    }

    /**
     * Approval with cancelled decision.
     */
    public function cancelled(): static
    {
        return $this->state(['decision' => ApprovalDecision::Cancelled]);
    }

    /**
     * Set the approval step.
     */
    public function forStep(int $step): static
    {
        return $this->state(['step' => $step]);
    }
}
