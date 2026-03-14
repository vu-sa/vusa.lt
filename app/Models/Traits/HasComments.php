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
        $sanitizedBody = strip_tags($body, '<p><br><strong><em><b><i><u><s><ul><ol><li><a><blockquote><code><pre><h1><h2><h3><h4><h5><h6>');

        $comment = $this->comments()->create(array_merge([
            'comment' => $sanitizedBody,
            'user_id' => auth()->id(),
            'commentable_id' => $this->id,
            'commentable_type' => get_class($this),
        ], $extra));

        CommentPosted::dispatch($comment);

        return $comment;
    }
}
