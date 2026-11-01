<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class AgendaItemPresenter
{
    /** @return array<string, mixed> */
    public static function redacted(AgendaItem $item): array
    {
        return [
            'id' => $item->id,
            'order' => $item->order,
            'is_private' => true,
            'is_redacted' => true,
            'title' => trim((string) $item->public_title) ?: __('meetings.privacy.hidden_title'),
        ];
    }

    public static function canRead(AgendaItem $item, ?User $user): bool
    {
        return $user !== null && Gate::forUser($user)->allows('view', $item);
    }

    /** @return array<string, mixed> */
    public static function forUser(AgendaItem $item, ?User $user): array
    {
        return $item->is_private && ! self::canRead($item, $user)
            ? self::redacted($item)
            : $item->toInternalArray();
    }

    /** @return array<string, mixed> */
    public static function publicItem(AgendaItem $item): array
    {
        if ($item->is_private) {
            return self::redacted($item);
        }

        $public = Arr::only($item->toInternalArray(), [
            'id', 'meeting_id', 'order', 'title', 'type', 'description',
            'brought_by_students', 'start_time', 'end_time', 'main_vote', 'votes', 'is_private',
        ]);

        if (isset($public['votes'])) {
            $public['votes'] = array_map(self::publicVote(...), $public['votes']);
        }
        if (isset($public['main_vote'])) {
            $public['main_vote'] = self::publicVote($public['main_vote']);
        }

        return $public;
    }

    /**
     * A vote's note is the recorder's own, so it never leaves Mano VU SA.
     *
     * @param  array<string, mixed>  $vote
     * @return array<string, mixed>
     */
    private static function publicVote(array $vote): array
    {
        return Arr::only($vote, ['id', 'title', 'is_main', 'is_consensus', 'student_vote', 'decision', 'student_benefit', 'order']);
    }

    /** @return array<string, mixed> */
    public static function publicMeeting(Meeting $meeting): array
    {
        return [
            ...Arr::only($meeting->toArray(), ['id', 'title', 'description', 'type', 'start_time', 'end_time', 'requires_student_perspective']),
            'agenda_items' => $meeting->agendaItems->map(fn (AgendaItem $item): array => self::publicItem($item))->all(),
        ];
    }
}
