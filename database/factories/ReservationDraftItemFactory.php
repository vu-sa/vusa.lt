<?php

namespace Database\Factories;

use App\Models\ReservationDraft;
use App\Models\ReservationDraftItem;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationDraftItem>
 */
class ReservationDraftItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_draft_id' => ReservationDraft::factory(),
            'resource_id' => Resource::factory(),
            'quantity' => 1,
        ];
    }
}
