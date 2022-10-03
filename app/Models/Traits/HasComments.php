<?php

namespace App\Models\Traits;

use App\Models\Comment;
use App\Models\User;

trait HasComments
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function addComment(string $comment, User $commentUser = null) : Comment
    {
        if ($commentUser === null) {
            $commentUser = auth()->user();
        }
        
        $commentModel = $this->comments()->create([
            'comment' => $comment,
            'user_id' => $commentUser->id,
            // get id and class type of the current object
            'commentable_id' => $this->id,
            'commentable_type' => get_class($this),
        ]);

        return $commentModel;
    }
}