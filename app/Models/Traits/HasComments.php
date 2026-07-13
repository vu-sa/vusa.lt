<?php

namespace App\Models\Traits;

use App\Events\CommentPosted;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Top-level comments only (thread roots), newest last.
     *
     * @return MorphMany<Comment, $this>
     */
    public function rootComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id')->orderBy('created_at');
    }

    /**
     * Create a comment (or threaded reply) on this model. The single creation
     * path: it resolves the thread root, extracts @mentions, and dispatches
     * CommentPosted for the notification pipeline.
     *
     * @param  array<string, mixed>  $attributes  Extra columns (e.g. kind, metadata).
     */
    public function comment(string $body, ?string $parentId = null, array $attributes = []): Comment
    {
        $threadRootId = null;

        if ($parentId !== null) {
            $parent = Comment::findOrFail($parentId);
            $threadRootId = $parent->thread_root_id ?? $parent->id;
        }

        // Extract mentions from the sanitized body so notifications can only
        // target ids that survive sanitization (the model mutator sanitizes on set).
        $body = Comment::sanitizeBody($body);

        $comment = $this->comments()->create(array_merge([
            'body' => $body,
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'thread_root_id' => $threadRootId,
            'mentioned_user_ids' => Comment::extractMentions($body),
        ], $attributes));

        CommentPosted::dispatch($comment);

        return $comment;
    }
}
