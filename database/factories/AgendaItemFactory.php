<?php

namespace Database\Factories;

use App\Enums\AgendaItemType;
use App\Models\AgendaItem;
use Database\Factories\Concerns\HasTranslatableFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaItem>
 */
class AgendaItemFactory extends Factory
{
    use HasTranslatableFactory;

    protected $model = \App\Models\Pivots\AgendaItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->translatable(),
            'order' => $this->faker->numberBetween(1, 10),
            'description' => $this->faker->optional()->passthrough($this->translatable()),
            'type' => null, // Default to unset type, requiring user to select
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
     * A pause in the sitting. Like informational and deferred items, it needs no vote.
     */
    public function break(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AgendaItemType::Break,
        ]);
    }

    /**
     * Add a student position to the agenda item.
     */
    public function withStudentPosition(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_position' => $this->translatable(),
        ]);
    }
}
