<?php

namespace App\Models\Traits;

use App\Events\CommentPosted;
use App\Models\Comment;

trait HasComments
{
    public function commentable()
    {
        return $this->morphTo();
    }
    
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function comment(string $body) : Comment
    {
        $comment = $this->comments()->create([
            'comment' => $body,
            'user_id' => auth()->user()->id,
            // get id and class type of the current object
            'commentable_id' => $this->id,
            'commentable_type' => get_class($this),
        ]);

        CommentPosted::dispatch($comment);

        return $comment;
    }
}