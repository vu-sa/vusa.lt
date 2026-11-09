<?php

namespace App\Actions;

use App\Enums\ResponsibilityPurpose;
use App\Models\User;
use App\Services\ResponsibilityResolver;
use Illuminate\Database\Eloquent\Collection;

class GetCoordinatedRepresentatives
{
    /** @return Collection<int, User> */
    public static function execute(User $coordinator): Collection
    {
        $institutionIds = app(ResponsibilityResolver::class)->institutionIdsFor($coordinator, ResponsibilityPurpose::StudentRepCoordination);

        return User::query()->whereHas('dutiables', fn ($terms) => $terms->current()
            ->whereHas('duty', fn ($duties) => $duties->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)->when(! $coordinator->isSuperAdmin(), fn ($query) => $query->whereIn('institution_id', $institutionIds))))
            ->with(['authorization_duties.institution.types', 'authorization_duties.types', 'authorization_duties.predecessors'])
            ->get();
    }

    public static function isCoordinatorOf(User $coordinator, User $rep): bool
    {
        $institutionIds = app(ResponsibilityResolver::class)->institutionIdsFor($coordinator, ResponsibilityPurpose::StudentRepCoordination);

        return $rep->dutiables()->current()->whereHas('duty', fn ($duties) => $duties
            ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)->when(! $coordinator->isSuperAdmin(), fn ($query) => $query->whereIn('institution_id', $institutionIds)))->exists();
    }
}
