<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgendaItem>
 */
class AgendaItemFactory extends Factory
{
    protected $model = \App\Models\Pivots\AgendaItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'order' => $this->faker->numberBetween(1, 10),
            'description' => $this->faker->optional()->paragraph,
        ];
    }

    /**
     * Create agenda items with sequential ordering for a specific meeting.
     */
    public function sequentialOrder(int $startOrder = 1): static
    {
        return $this->sequence(fn ($sequence) => [
            'order' => $startOrder + $sequence->index
        ]);
    }
}
