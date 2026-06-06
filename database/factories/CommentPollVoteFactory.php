<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentPollVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommentPollVote>
 */
class CommentPollVoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory(),
            'user_id' => User::factory(),
            'option_id' => (string) $this->faker->numberBetween(0, 3),
        ];
    }
}
