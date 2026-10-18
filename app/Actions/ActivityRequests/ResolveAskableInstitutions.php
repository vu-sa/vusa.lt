<?php

namespace App\Actions\ActivityRequests;

use App\Enums\Responsibility;
use App\Models\Institution;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\ResponsibilityResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * The institutions $user may ask about activity — the batch form of InstitutionCheckInPolicy::askAboutActivity().
 */
class ResolveAskableInstitutions
{
    public function __construct(
        private readonly ModelAuthorizer $authorizer,
        private readonly ResponsibilityResolver $responsibilities,
    ) {}

    /** @return Builder<Institution> */
    public function query(User $user): Builder
    {
        $scope = $this->authorizer->scope($user, 'institutions.update.padalinys');
        $coordinatedIds = $this->responsibilities->institutionIdsFor($user, Responsibility::StudentRepCoordination);

        return Institution::query()
            ->when(! $scope->isAllScope, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereIn('tenant_id', $scope->tenantIds())
                ->orWhereIn('id', $coordinatedIds)));
    }
}
