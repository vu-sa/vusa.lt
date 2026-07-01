<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Splits the people who should hear about a new comment into two groups:
 *
 *  - mentioned: users explicitly @mentioned in the body (personal, high signal).
 *  - audience:  for a root comment, the commentable's full audience (reps); for
 *               a reply, only the thread participants (so replies don't blast
 *               everyone). Both groups always exclude the comment's author, and
 *               the audience group excludes anyone already in `mentioned`.
 */
class CommentRecipientResolver
{
    public function __construct(protected CommentableMentionResolver $mentionResolver) {}

    /**
     * Users explicitly mentioned in the comment, minus the author.
     *
     * @return Collection<int, User>
     */
    public function mentioned(Comment $comment): Collection
    {
        $ids = collect($comment->mentioned_user_ids ?? [])
            ->reject(fn ($id) => (string) $id === (string) $comment->user_id)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->get();
    }

    /**
     * The non-mention audience for the comment, minus the author and anyone
     * already covered by the mention group.
     *
     * @return Collection<int, User>
     */
    public function audience(Comment $comment): Collection
    {
        $commentable = $comment->commentable;

        if ($commentable === null) {
            return collect();
        }

        $base = $comment->parent_id === null
            ? $this->audienceFor($commentable)
            : $this->threadParticipants($comment);

        $excludedIds = $this->mentioned($comment)
            ->pluck('id')
            ->push($comment->user_id)
            ->map(fn ($id) => (string) $id)
            ->all();

        return $base
            ->reject(fn (User $user) => in_array((string) $user->id, $excludedIds, true))
            ->unique('id')
            ->values();
    }

    /**
     * The audience for a root comment: the curated mention pool (representatives)
     * for meetings / agenda items / institutions, falling back to the
     * commentable's own `users` relation for everything else (reservations, …).
     *
     * @return Collection<int, User>
     */
    protected function audienceFor(Model $commentable): Collection
    {
        $pool = $this->mentionResolver->audienceUsers($commentable);

        if ($pool->isNotEmpty()) {
            return $pool;
        }

        if (method_exists($commentable, 'users')) {
            $users = $commentable->users;

            return $users instanceof Collection ? $users : collect($users)->filter()->values();
        }

        return collect();
    }

    /**
     * Everyone who has already posted in this comment's thread (root author and
     * every reply author), so replies notify the conversation, not the room.
     *
     * @return Collection<int, User>
     */
    protected function threadParticipants(Comment $comment): Collection
    {
        $rootId = $comment->thread_root_id ?? $comment->parent_id;

        $userIds = Comment::query()
            ->where(fn ($q) => $q->where('thread_root_id', $rootId)->orWhere('id', $rootId))
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $userIds)->get();
    }
}
