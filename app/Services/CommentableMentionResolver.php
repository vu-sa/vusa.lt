<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Resolves the set of users who can be @mentioned in a comment on a given
 * commentable. The pool is intentionally limited to people who can already
 * view the parent (representatives / participants), so mentioning never leaks
 * identities or notifies users without access.
 */
class CommentableMentionResolver
{
    /**
     * @return array<int, array{id: string, name: string, profile_photo_path: string|null}>
     */
    public function resolve(Model $commentable): array
    {
        return $this->audienceUsers($commentable)
            ->map(fn ($user) => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'profile_photo_path' => $user->profile_photo_path,
            ])
            ->values()
            ->all();
    }

    /**
     * The User models who can already view the commentable — the audience that
     * may be @mentioned and that the notification pipeline targets. Empty for
     * commentables without a known audience (e.g. an orphaned agenda item).
     *
     * @return Collection<int, User>
     */
    public function audienceUsers(Model $commentable): Collection
    {
        $users = match (true) {
            $commentable instanceof Meeting => $this->meetingUsers($commentable),
            $commentable instanceof AgendaItem => $commentable->meeting
                ? $this->meetingUsers($commentable->meeting)
                : collect(),
            $commentable instanceof Institution => $commentable->users()->get(),
            default => collect(),
        };

        return $users->unique('id')->values();
    }

    /**
     * Active student representatives plus any meeting participants.
     */
    private function meetingUsers(Meeting $meeting): Collection
    {
        return $meeting->getRepresentativesActiveAt()
            ->concat($meeting->users)
            ->values();
    }
}
