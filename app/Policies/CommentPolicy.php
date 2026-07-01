<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * "See it → discuss it": comment authorization derives from the parent
 * commentable. Anyone who can `view` the parent can read, post, react and
 * resolve. Editing/deleting your own comment is the author's right; deleting
 * someone else's requires `update` on the parent (moderation).
 *
 * Create is authorized in the controller against the parent (no Comment
 * instance exists yet) via Gate::authorize('view', $commentable).
 */
class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        return $comment->commentable !== null
            && Gate::forUser($user)->allows('view', $comment->commentable);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->isAuthor($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->isAuthor($user)) {
            return true;
        }

        return $comment->commentable !== null
            && Gate::forUser($user)->allows('update', $comment->commentable);
    }

    public function resolve(User $user, Comment $comment): bool
    {
        return $comment->commentable !== null
            && Gate::forUser($user)->allows('view', $comment->commentable);
    }

    public function react(User $user, Comment $comment): bool
    {
        return $comment->commentable !== null
            && Gate::forUser($user)->allows('view', $comment->commentable);
    }

    public function vote(User $user, Comment $comment): bool
    {
        return $comment->commentable !== null
            && Gate::forUser($user)->allows('view', $comment->commentable);
    }
}
