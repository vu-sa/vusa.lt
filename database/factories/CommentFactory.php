<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // $commentable = $this->commentable();

        return [
            'comment' => $this->faker->paragraph(),
            'user_id' => User::factory(),
        ];
    }

    // public function configure()
    // {
    //     return $this->for(
    //         static::factoryForModel($this->commentable()),
    //         'commentable',
    //     );
    // }

}
