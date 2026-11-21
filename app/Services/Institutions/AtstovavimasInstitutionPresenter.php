<?php

namespace App\Services\Institutions;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Pivots\Dutiable;
use App\Services\InstitutionActivityStatusService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class AtstovavimasInstitutionPresenter
{
    /**
     * @param  Collection<int, Institution>  $institutions
     * @return list<array{
     *     id: string, name: string|null, short_name: string|null, tenant: array{id: int, shortname: string}|null,
     *     types: list<array{id: int, title: string|null, slug: string|null}>, is_internal: bool, has_public_meetings: bool,
     *     meeting_periodicity_days: int, upcoming_meetings_count: int, incomplete_meetings_count: int|null,
     *     last_meeting_date: string|null, next_meeting_date: string|null, last_activity_at: string|null,
     *     activity_status: array{status: string, requires_action: bool, priority: int, periodicity_days: int, effective_days_since_activity: int|null, progress_percentage: int|null, last_activity_type: string|null, last_activity_at: string|null, last_meeting_at: string|null, next_meeting_at: string|null, active_check_in_until: string|null, last_completed_check_in_at: string|null},
     *     active_check_in: array{id: string, institution_id: string, start_date: string, end_date: string, note: string|null}|null,
     *     check_ins: list<array{id: string, institution_id: string, start_date: string, end_date: string, note: string|null}>,
     *     membership_periods: list<array{user: array{id: string, name: string, profile_photo_path: string|null}, duty_id: string, start_date: string, end_date: string|null}>,
     *     is_secretary: bool, is_administered: bool, is_related: bool, authorized: bool,
     *     relationship_direction: string|null, relationship_type: string|null, source_institution_id: string|null
     * }>
     */
    public static function rows(Collection $institutions): array
    {
        $activity = app(InstitutionActivityStatusService::class);
        $today = today();
        $rows = [];

        foreach ($institutions as $institution) {
            $tenant = $institution->tenant;
            $activeCheckIn = $institution->checkIns->where('end_date', '>=', $today)->where('start_date', '<=', $today)->first();
            $authorized = $institution->getAttribute('authorized') !== false;
            $related = (bool) $institution->getAttribute('is_related');
            $secretary = (bool) $institution->getAttribute('is_secretary');
            $activityStatus = $activity->resolve($institution)->toArray();
            $types = [];
            foreach ($institution->types as $type) {
                $types[] = ['id' => $type->id, 'title' => $type->getTranslation('title', app()->getLocale()), 'slug' => $type->slug];
            }

            $rows[] = [
                'id' => $institution->id,
                'name' => $institution->getTranslation('name', app()->getLocale()),
                'short_name' => $institution->getTranslation('short_name', app()->getLocale()),
                'tenant' => $tenant === null ? null : ['id' => $tenant->id, 'shortname' => $tenant->shortname],
                'types' => $types,
                'is_internal' => $institution->governance_scope->isInternal(),
                'has_public_meetings' => $institution->has_public_meetings,
                'meeting_periodicity_days' => $institution->meeting_periodicity_days,
                'upcoming_meetings_count' => (int) $institution->getAttribute('upcoming_meetings_count'),
                'incomplete_meetings_count' => $authorized ? (int) $institution->getAttribute('incomplete_meetings_count') : null,
                'last_meeting_date' => $related ? null : $activityStatus['last_meeting_at'],
                'next_meeting_date' => $related ? null : $activityStatus['next_meeting_at'],
                'last_activity_at' => $related ? null : $institution->getAttribute('last_activity_at'),
                'activity_status' => $activityStatus,
                'active_check_in' => $activeCheckIn === null ? null : self::checkIn($activeCheckIn),
                'check_ins' => $institution->checkIns->map(self::checkIn(...))->values()->all(),
                'membership_periods' => self::membershipPeriods($institution),
                'is_secretary' => $secretary,
                'is_administered' => $secretary,
                'is_related' => $related,
                'authorized' => $authorized,
                'relationship_direction' => $institution->getAttribute('relationship_direction'),
                'relationship_type' => $institution->getAttribute('relationship_type'),
                'source_institution_id' => $institution->getAttribute('source_institution_id'),
            ];
        }

        return $rows;
    }

    /** @return array{id: string, institution_id: string, start_date: string, end_date: string, note: string|null} */
    private static function checkIn(InstitutionCheckIn $checkIn): array
    {
        return [
            'id' => $checkIn->id,
            'institution_id' => $checkIn->institution_id,
            'start_date' => $checkIn->start_date->toDateString(),
            'end_date' => $checkIn->end_date->toDateString(),
            'note' => $checkIn->note,
        ];
    }

    /** @return list<array{user: array{id: string, name: string, profile_photo_path: string|null}, duty_id: string, start_date: string, end_date: string|null}> */
    private static function membershipPeriods(Institution $institution): array
    {
        $periods = [];
        foreach ($institution->duties as $duty) {
            foreach ($duty->users as $user) {
                $term = $user->getRelation('pivot');
                $start = $term instanceof Dutiable ? $term->getAttribute('start_date') : null;
                if (! $start instanceof CarbonInterface) {
                    continue;
                }

                $periods[] = [
                    'user' => ['id' => (string) $user->id, 'name' => $user->name, 'profile_photo_path' => $user->profile_photo_path],
                    'duty_id' => (string) $duty->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $term->end_date?->toDateString(),
                ];
            }
        }

        return $periods;
    }
}
