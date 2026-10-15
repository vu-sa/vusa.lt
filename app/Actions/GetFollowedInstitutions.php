<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;

/**
 * "Sekamos institucijos" on Pradžia and ViSAK: the institutions a user chose to follow.
 *
 * Duty institutions are not listed here; they already are the user's own.
 */
class GetFollowedInstitutions
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function execute(User $user, ?int $limit = null): array
    {
        $query = Institution::query()
            ->whereHas('followers', fn ($query) => $query->whereKey($user->getKey()))
            ->orderBy('name->lt');
        $total = (clone $query)->count();

        if ($limit !== null) {
            $query->limit($limit);
        }

        $activityStatusService = app(InstitutionActivityStatusService::class);

        // All check-ins, not only current ones: resolve() needs completed ones for lastActivityAt.
        $items = $query->with(['types', 'meetings:id,start_time', 'checkIns'])
            ->get()
            ->map(fn (Institution $institution): array => [
                'id' => (string) $institution->id,
                'name' => $institution->name,
                'activity_status' => $activityStatusService->resolve($institution)->status->value,
            ])
            ->values()
            ->all();

        return ['items' => $items, 'total' => $total];
    }
}
