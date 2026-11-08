<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use Illuminate\Support\Collection;

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
        $institutionIds = self::institutionIds($user);

        if ($institutionIds->isEmpty()) {
            return ['items' => [], 'total' => 0];
        }

        $query = Institution::query()
            ->whereKey($institutionIds)
            ->orderBy('name->lt');
        $total = $institutionIds->count();

        if ($limit !== null) {
            $query->limit($limit);
        }

        $activityStatusService = app(InstitutionActivityStatusService::class);

        $items = $activityStatusService->withSummary($query)
            ->get()
            ->map(fn (Institution $institution): array => [
                'id' => (string) $institution->id,
                'name' => $institution->name,
                'is_active' => (bool) $institution->is_active,
                'activity_status' => $activityStatusService->resolve($institution)->status->value,
            ])
            ->values()
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /** @return Collection<int, string> */
    public static function institutionIds(User $user): Collection
    {
        // Include the request in the memo key when a worker handles several requests.
        $request = request();

        return once(fn (): Collection => $user->followedInstitutions()->pluck('institutions.id')->map(fn ($id): string => (string) $id));
    }
}
