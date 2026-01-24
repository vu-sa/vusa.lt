<?php

namespace Database\Factories;

use App\Enums\AgendaItemType;
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
            'type' => AgendaItemType::Informational,
        ];
    }

    /**
     * Create agenda items with sequential ordering for a specific meeting.
     */
    public function sequentialOrder(int $startOrder = 1): static
    {
        return $this->sequence(fn ($sequence) => [
            'order' => $startOrder + $sequence->index,
        ]);
    }

    /**
     * Mark agenda item as voting type with vote status set.
     */
    public function voting(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AgendaItemType::Voting,
        ]);
    }

    /**
     * Mark agenda item as informational type.
     */
    public function informational(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AgendaItemType::Informational,
        ]);
    }

    /**
     * Mark agenda item as deferred type.
     */
    public function deferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AgendaItemType::Deferred,
        ]);
    }

    /**
     * Add a student position to the agenda item.
     */
    public function withStudentPosition(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_position' => $this->faker->paragraph,
        ]);
    }
}
