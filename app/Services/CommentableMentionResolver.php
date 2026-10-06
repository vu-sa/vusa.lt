<?php

namespace App\Services;

use App\Actions\GetInstitutionMembers;
use App\Actions\GetInstitutionSecretaries;
use App\Models\Duty;
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
     * Whether audienceUsers() knows who this commentable's audience is. When it does, an
     * empty result means "nobody right now", not "fall back to the model's `users` relation",
     * which for an institution or a duty is everyone who ever held a seat.
     */
    public function hasCuratedAudience(Model $commentable): bool
    {
        return $commentable instanceof Meeting
            || $commentable instanceof AgendaItem
            || $commentable instanceof Institution
            || $commentable instanceof Reservation
            || $commentable instanceof Duty
            || $commentable instanceof SupportRequest;
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
            // Current holders only: `$duty->users` is every person who ever held the seat.
            $commentable instanceof Duty => $commentable->current_users()->get(),
            $commentable instanceof Reservation => $commentable->users()->get(),
            $commentable instanceof SupportRequest => $this->supportRequestUsers($commentable),
            default => collect(),
        };

        return $users->unique('id')->values();
    }

    /**
     * Everyone holding a duty in the meeting's institutions on its own date, plus the
     * nominated secretaries (O22).
     *
     * `$meeting->users` used to be concatenated here, but that deep relation reaches
     * every person who ever held a duty in the institution — mentioning a meeting
     * notified holders who left years before it was scheduled.
     */
    private function meetingUsers(Meeting $meeting): Collection
    {
        return GetInstitutionMembers::forMeeting($meeting)
            ->concat(GetInstitutionSecretaries::forMeeting($meeting))
            ->values();
    }

    /**
     * Current duty holders plus secretaries. Institution::users() is the all-time
     * deep relation and must not be used for an audience.
     *
     * @return Collection<int, User>
     */
    private function institutionUsers(Institution $institution): Collection
    {
        return GetInstitutionMembers::execute($institution)
            ->concat(GetInstitutionSecretaries::execute($institution))
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
