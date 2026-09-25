<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;

/**
 * "Artimiausi posėdžiai": the next two months of meetings in the user's duty institutions and
 * the ones they follow. A meeting reached only through a follow is marked `is_followed`.
 */
class GetUpcomingMeetingsForUser
{
    /**
     * @return array{items: list<array{id: string, title: string, start_time: string, institution_name: string|null, institution_id: string|null, tenant_id: int|null, is_followed: bool}>, total: int}
     */
    public static function execute(User $user, int $limit = 20): array
    {
        $dutyInstitutionIds = $user->authorization_duties()->pluck('duties.institution_id')->filter()->unique()->map(fn ($id): string => (string) $id);
        $followedInstitutionIds = $user->followedInstitutions()->pluck('institutions.id')->map(fn ($id): string => (string) $id);
        $institutionIds = $dutyInstitutionIds->merge($followedInstitutionIds)->unique()->values();

        if ($institutionIds->isEmpty()) {
            return ['items' => [], 'total' => 0];
        }

        $query = Meeting::query()
            ->whereHas('institutions', fn ($q) => $q->whereIn('institutions.id', $institutionIds))
            ->where('start_time', '>=', now()->startOfDay())
            ->where('start_time', '<', now()->addMonths(2));

        $total = (clone $query)->count();

        $items = $query->orderBy('start_time')
            ->with(['institutions:id,name,tenant_id'])
            ->take($limit)
            ->get()
            ->map(function (Meeting $meeting) use ($dutyInstitutionIds, $followedInstitutionIds): array {
                $dutyInstitution = $meeting->institutions->first(fn (Institution $institution): bool => $dutyInstitutionIds->contains((string) $institution->id));
                $institution = $dutyInstitution
                    ?? $meeting->institutions->first(fn (Institution $institution): bool => $followedInstitutionIds->contains((string) $institution->id))
                    ?? $meeting->institutions->first();

                return [
                    'id' => (string) $meeting->id,
                    'title' => (string) $meeting->title,
                    'start_time' => $meeting->start_time->toISOString(),
                    'institution_name' => $institution?->name,
                    'institution_id' => $institution === null ? null : (string) $institution->id,
                    'tenant_id' => $institution?->tenant_id,
                    'is_followed' => $dutyInstitution === null,
                ];
            })
            ->values()
            ->all();

        return ['items' => $items, 'total' => $total];
    }
}
