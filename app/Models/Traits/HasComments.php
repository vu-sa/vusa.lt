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

    /**
     * @param  array<string, mixed>  $extra
     */
    public function comment(string $body, array $extra = []): Comment
    {
        $comment = $this->comments()->create(array_merge([
            'comment' => $body,
            'user_id' => auth()->id(),
            'commentable_id' => $this->id,
            'commentable_type' => get_class($this),
        ], $extra));

        CommentPosted::dispatch($comment);

        return $comment;
    }
}
