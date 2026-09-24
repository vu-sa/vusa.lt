<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Meetings the user changed moments ago, in the shape of a Posėdžiai search hit.
 *
 * The collection reads from Typesense, which lags the database after a write. O1's answer is
 * a database read for "changed by me in the last minute", pinned above the results until the
 * index has caught up — so a rep who just recorded a meeting sees it at once.
 */
class GetRecentlyChangedMeetings
{
    /** How long a change is pinned; comfortably longer than Scout's queue plus indexing. */
    public const WINDOW_MINUTES = 2;

    /**
     * @return Collection<int, covariant array<string, mixed>>
     */
    public static function execute(User $user): Collection
    {
        $ids = Activity::query()
            ->causedBy($user)
            ->where('root_subject_type', 'meeting')
            ->where('created_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->pluck('root_subject_id')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return Meeting::query()
            ->whereIn('id', $ids)
            ->with(['institutions.tenant', 'agendaItems.votes'])
            ->get()
            ->filter(fn (Meeting $meeting): bool => $user->can('view', $meeting))
            ->map(function (Meeting $meeting): array {
                $institution = $meeting->institutions->first();

                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'start_time' => $meeting->start_time->timestamp,
                    'type' => $meeting->type?->value,
                    'institution_ids' => $meeting->institutions->pluck('id')->all(),
                    'institution_name_lt' => $institution?->getTranslation('name', 'lt'),
                    'institution_name_en' => $institution?->getTranslation('name', 'en'),
                    'tenant_shortname' => $institution?->tenant?->shortname,
                    'agenda_items_count' => $meeting->agendaItems->count(),
                    'completion_status' => $meeting->completion_status,
                ];
            })
            ->values();
    }
}
