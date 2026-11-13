<?php

namespace App\Policies;

use App\Enums\CommentKind;
use App\Models\Comment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/** Discussion permissions derive from the parent; only topics can be closed. */
class CommentPolicy
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function view(User $user, Comment $comment): bool
    {
        return $this->canViewCommentable($user, $comment);
    }

    public function update(User $user, Comment $comment): bool
    {
        return ! $comment->isErased() && $comment->isEditableBy($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->isErased()) {
            return false;
        }

        if ($comment->isAuthor($user)) {
            return true;
        }

        return $this->moderatesParent($user, $comment);
    }

    public function resolve(User $user, Comment $comment): bool
    {
        return ! $comment->isErased() && $comment->kind === CommentKind::Topic
            && ($comment->isAuthor($user) || $this->moderatesParent($user, $comment));
    }

    private function moderatesParent(User $user, Comment $comment): bool
    {
        /** @var Model|null $commentable */
        $commentable = $comment->commentable;

        return $commentable !== null && $this->canModerate($user, $commentable);
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

    public function canModerate(User $user, Model $commentable): bool
    {
        if (Gate::forUser($user)->allows('update', $commentable)) {
            return true;
        }

        if ($this->authorizer->scope($user, 'comments.delete.*')->isAllScope) {
            return true;
        }

        $tenantIds = $this->authorizer->tenants($user, 'comments.delete.padalinys')->pluck('id');

        return $tenantIds->isNotEmpty() && $this->commentableTenantIds($commentable)->intersect($tenantIds)->isNotEmpty();
    }

    /** @return Collection<int, int|string> */
    private function commentableTenantIds(Model $commentable): Collection
    {
        if (method_exists($commentable, 'tenant')) {
            $tenant = $commentable->getRelationValue('tenant');

            return $tenant instanceof Tenant ? collect([$tenant->getKey()]) : collect();
        }

        return method_exists($commentable, 'tenants')
            ? $commentable->tenants()->pluck('tenants.id')
            : collect();
    }
}
