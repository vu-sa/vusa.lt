<?php

namespace Database\Factories;

use App\Models\Pivots\AgendaItem;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agenda_item_id' => AgendaItem::factory(),
            'is_main' => true,
            'title' => null,
            'student_vote' => null,
            'decision' => null,
            'student_benefit' => null,
            'note' => null,
            'order' => 0,
        ];
    }

    /**
     * Mark as main vote.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
        ]);
    }

    /**
     * Mark as additional (non-main) vote.
     */
    public function additional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => false,
        ]);
    }

    /**
     * Set a title for this vote.
     */
    public function withTitle(?string $title = null): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title ?? $this->faker->sentence(4),
        ]);
    }

    /**
     * Set vote with complete data (all three fields filled).
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_vote' => $this->faker->randomElement(['positive', 'negative', 'neutral']),
            'decision' => $this->faker->randomElement(['positive', 'negative', 'neutral']),
            'student_benefit' => $this->faker->randomElement(['positive', 'negative', 'neutral']),
        ]);
    }

    /**
     * Set vote where student position was accepted (vote matches decision).
     */
    public function aligned(): static
    {
        $value = $this->faker->randomElement(['positive', 'negative', 'neutral']);

        return $this->state(fn (array $attributes) => [
            'student_vote' => $value,
            'decision' => $value,
            'student_benefit' => $this->faker->randomElement(['positive', 'negative', 'neutral']),
        ]);
    }

    /**
     * Set vote where student position was not accepted (vote doesn't match decision).
     */
    public function misaligned(): static
    {
        $studentVote = $this->faker->randomElement(['positive', 'negative']);
        $decision = $studentVote === 'positive' ? 'negative' : 'positive';

        return $this->state(fn (array $attributes) => [
            'student_vote' => $studentVote,
            'decision' => $decision,
            'student_benefit' => $this->faker->randomElement(['positive', 'negative', 'neutral']),
        ]);
    }

    /**
     * Set positive outcome (decision approved, students voted for, beneficial to students).
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_vote' => 'positive',
            'decision' => 'positive',
            'student_benefit' => 'positive',
        ]);
    }

    /**
     * Set negative outcome (decision rejected, students voted against, not beneficial).
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_vote' => 'negative',
            'decision' => 'negative',
            'student_benefit' => 'negative',
        ]);
    }

    /**
     * Add a note to the vote.
     */
    public function withNote(?string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'note' => $note ?? $this->faker->paragraph,
        ]);
    }
}
