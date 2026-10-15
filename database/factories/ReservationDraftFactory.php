<?php

namespace Database\Factories;

use App\Models\ReservationDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationDraft>
 */
class ReservationDraftFactory extends Factory
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
            'name' => null,
            'description' => null,
            'start_time' => null,
            'end_time' => null,
        ];
    }

    public function withPeriod(?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null): static
    {
        return $this->state(fn () => [
            'start_time' => $start ?? now()->addDay()->setTime(9, 0),
            'end_time' => $end ?? now()->addDays(3)->setTime(17, 0),
        ]);
    }
}
