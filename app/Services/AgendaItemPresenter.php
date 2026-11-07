<?php

namespace App\Services;

use App\Enums\AgendaItemType;
use App\Enums\TenantType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class AgendaItemPresenter
{
    /** @return array{id: string, order: int, is_private: true, is_redacted: true, title: string} */
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

    /** @return array{id: string, order: int, is_private: true, is_redacted: true, title: string}|array{id: string, meeting_id: string, created_at: string, updated_at: string, order: int, brought_by_students: bool, type: AgendaItemType|null, start_time: string|null, end_time: string|null, title: string|null, description: string|null, student_position: string|null, is_private: bool, public_title: string|null, votes: list<array{id: string, agenda_item_id: string, is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null, order: int, created_at: string|null, updated_at: string|null, title: string|null, note: string|null}>, comments_count: int, has_notes: bool, tenants: list<array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}>|null, meeting: array<string, mixed>|null} */
    public static function forUser(AgendaItem $item, ?User $user): array
    {
        return $item->is_private && ! self::canRead($item, $user)
            ? self::redacted($item)
            : self::full($item);
    }

    /** @return array{id: string, meeting_id: string, created_at: string, updated_at: string, order: int, brought_by_students: bool, type: AgendaItemType|null, start_time: string|null, end_time: string|null, title: string|null, description: string|null, student_position: string|null, is_private: bool, public_title: string|null, votes: list<array{id: string, agenda_item_id: string, is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null, order: int, created_at: string|null, updated_at: string|null, title: string|null, note: string|null}>, comments_count: int, has_notes: bool, tenants: list<array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}>|null, meeting: array<string, mixed>|null} */
    public static function full(AgendaItem $item): array
    {
        $attributes = $item->attributesToArray();
        $votes = [];
        foreach ($item->relationLoaded('votes') ? $item->votes : [] as $vote) {
            $votes[] = self::internalVote($vote);
        }

        $payload = [
            'id' => $attributes['id'],
            'meeting_id' => $attributes['meeting_id'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'order' => $attributes['order'],
            'brought_by_students' => $attributes['brought_by_students'],
            'type' => $item->type,
            'start_time' => $attributes['start_time'],
            'end_time' => $attributes['end_time'],
            'title' => $attributes['title'] === null ? null : (string) $item->title,
            'description' => $attributes['description'] === null ? null : (string) $item->description,
            'student_position' => $attributes['student_position'] === null ? null : (string) $item->student_position,
            'is_private' => $attributes['is_private'],
            'public_title' => $attributes['public_title'] === null ? null : (string) $item->public_title,
            'votes' => $votes,
            'comments_count' => (int) $item->getAttribute('comments_count'),
            'has_notes' => (bool) $item->getAttribute('has_notes'),
        ];

        $tenants = null;
        if ($item->relationLoaded('tenants')) {
            $tenants = [];
            foreach ($item->tenants as $tenant) {
                $tenants[] = MeetingPresenter::tenant($tenant);
            }
        }
        $payload['tenants'] = $tenants;
        $payload['meeting'] = $item->relationLoaded('meeting') ? $item->meeting?->toArray() : null;

        return $payload;
    }

    /** @return array{id: string, agenda_item_id: string, is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null, order: int, created_at: string|null, updated_at: string|null, title: string|null, note: string|null} */
    private static function internalVote(Vote $vote): array
    {
        $attributes = $vote->attributesToArray();

        return [
            'id' => $attributes['id'],
            'agenda_item_id' => $attributes['agenda_item_id'],
            'is_main' => $attributes['is_main'],
            'is_consensus' => $attributes['is_consensus'],
            'student_vote' => $attributes['student_vote'],
            'decision' => $attributes['decision'],
            'student_benefit' => $attributes['student_benefit'],
            'order' => $attributes['order'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'title' => $attributes['title'] === null ? null : (string) $vote->title,
            'note' => $attributes['note'] === null ? null : (string) $vote->note,
        ];
    }

    /** @return array<string, mixed> */
    public static function publicItem(AgendaItem $item): array
    {
        if ($item->is_private) {
            return self::redacted($item);
        }

        $public = Arr::only($item->toInternalArray(), [
            'id', 'meeting_id', 'order', 'title', 'type', 'description', 'student_position',
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
