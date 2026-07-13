<?php

namespace Database\Factories;

use App\Enums\CommentKind;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => CommentKind::Comment,
            'body' => '<p>'.$this->faker->paragraph().'</p>',
            'user_id' => User::factory(),
        ];
    }

    /**
     * A reply to a given parent comment (inherits its thread root).
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'thread_root_id' => $parent->thread_root_id ?? $parent->id,
            'commentable_type' => $parent->commentable_type,
            'commentable_id' => $parent->commentable_id,
        ]);
    }

    /**
     * A resolved thread root.
     */
    public function resolved(?User $by = null): static
    {
        return $this->state(fn () => [
            'resolved_at' => now(),
            'resolved_by' => $by?->id ?? User::factory(),
        ]);
    }
}
