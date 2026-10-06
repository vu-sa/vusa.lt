<?php

namespace App\Services;

use App\Actions\GetInstitutionAdministrators;
use App\Actions\GetInstitutionMembers;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\SupportRequest;
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
        $users = $this->audienceUsers($commentable);

        // Role members can see a shared request, but every root comment would be noise for them.
        if ($commentable instanceof SupportRequest) {
            $users = $users->concat($commentable->roleUsers())->unique('id');
        }

        return $users
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
            $commentable instanceof Institution => $this->institutionUsers($commentable),
            $commentable instanceof Reservation => $commentable->users()->get(),
            $commentable instanceof SupportRequest => $this->supportRequestUsers($commentable),
            default => collect(),
        };

        return $users->unique('id')->values();
    }

    /**
     * Everyone holding a duty in the meeting's institutions on its own date, plus the
     * nominated administrators.
     *
     * `$meeting->users` used to be concatenated here, but that deep relation reaches
     * every person who ever held a duty in the institution — mentioning a meeting
     * notified holders who left years before it was scheduled.
     */
    private function meetingUsers(Meeting $meeting): Collection
    {
        return GetInstitutionMembers::forMeeting($meeting)
            ->concat(GetInstitutionAdministrators::forMeeting($meeting))
            ->values();
    }

    /**
     * Current duty holders plus administrators. Institution::users() is the all-time
     * deep relation and must not be used for an audience.
     *
     * @return Collection<int, User>
     */
    private function institutionUsers(Institution $institution): Collection
    {
        return GetInstitutionMembers::execute($institution)
            ->concat(GetInstitutionAdministrators::execute($institution))
            ->values();
    }

    /**
     * The reporter, the assignee and the people added to the request.
     *
     * @return Collection<int, User>
     */
    private function supportRequestUsers(SupportRequest $supportRequest): Collection
    {
        return collect([$supportRequest->creator, $supportRequest->assignedTo])
            ->concat($supportRequest->involvedUsers)
            ->filter()
            ->values();
    }
}
