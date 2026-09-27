<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * "See it → discuss it": comment authorization derives from the parent
 * commentable. Anyone who can `view` the parent can read, post, react and
 * resolve. Editing/deleting your own comment is the author's right; deleting
 * someone else's requires `update` on the parent, or `comments.delete.padalinys`
 * for the parent's padalinys, or `comments.delete.*` (moderation).
 *
 * Create is authorized in the controller against the parent (no Comment
 * instance exists yet) via Gate::authorize('view', $commentable).
 */
class CommentPolicy
{
    public function __construct(private ModelAuthorizer $authorizer) {}

    public function view(User $user, Comment $comment): bool
    {
        return $this->canViewCommentable($user, $comment);
    }

    public function update(User $user, Comment $comment): bool
    {
        return ! $comment->isErased() && $comment->isAuthor($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->isErased()) {
            return false;
        }

        if ($comment->isAuthor($user)) {
            return true;
        }

        return $this->canUpdateCommentable($user, $comment) || $this->moderates($user, $comment);
    }

    public function resolve(User $user, Comment $comment): bool
    {
        return ! $comment->isErased() && $this->canViewCommentable($user, $comment);
    }

    public function react(User $user, Comment $comment): bool
    {
        return ! $comment->isErased() && $this->canViewCommentable($user, $comment);
    }

    public function vote(User $user, Comment $comment): bool
    {
        return ! $comment->isErased() && $this->canViewCommentable($user, $comment);
    }

    private function canViewCommentable(User $user, Comment $comment): bool
    {
        /** @var Model|null $commentable */
        $commentable = $comment->commentable;

        return $commentable !== null
            && Gate::forUser($user)->allows('view', $commentable);
    }

    private function moderates(User $user, Comment $comment): bool
    {
        if ($this->authorizer->scope($user, 'comments.delete.*')->isAllScope) {
            return true;
        }

        $tenantIds = $this->authorizer->tenants($user, 'comments.delete.padalinys')->pluck('id');

        return $tenantIds->isNotEmpty() && $this->commentableTenantIds($comment)->intersect($tenantIds)->isNotEmpty();
    }

    /**
     * Records without a padalinys (SharePoint files, support requests) only yield to `comments.delete.*`.
     *
     * @return Collection<int, int|string>
     */
    private function commentableTenantIds(Comment $comment): Collection
    {
        $commentable = $comment->commentable;

        if ($commentable === null) {
            return collect();
        }

        if (method_exists($commentable, 'tenant')) {
            $tenant = $commentable->getRelationValue('tenant');

            return $tenant instanceof Tenant ? collect([$tenant->getKey()]) : collect();
        }

        return method_exists($commentable, 'tenants')
            ? $commentable->tenants()->pluck('tenants.id')
            : collect();
    }

    private function canUpdateCommentable(User $user, Comment $comment): bool
    {
        /** @var Model|null $commentable */
        $commentable = $comment->commentable;

        return $commentable !== null
            && Gate::forUser($user)->allows('update', $commentable);
    }
}
