<?php

namespace App\Actions;

use App\Http\Requests\IndexUserRequest;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Builder;

/**
 * The member list's base query — one definition for the Inertia page and its API twin.
 *
 * Tenant-scoped user administration reaches people with a duty history (or a role); the tenant
 * scope itself is applied by the caller through `applyTanstackFilters(... 'tenantRelation' =>
 * 'tenants')`. Dutyless, roleless accounts are never part of this list (.ai/rules/policies-http-requests.md).
 */
final class BuildUserIndexQuery
{
    /**
     * @return Builder<User>
     */
    public static function execute(IndexUserRequest $request, ModelAuthorizer $authorizer): Builder
    {
        $query = User::query()
            ->where(fn (Builder $query) => $query
                ->whereHas('duties')
                ->orWhereHas('roles'))
            ->with([
                'duties:id,institution_id',
                'duties.institution:id,tenant_id',
                'duties.institution.tenant:id,shortname',
            ])->withCount('duties');

        $futureDuty = $request->getFilters()['future_duty'] ?? $request->validated('future_duty');

        return $futureDuty === 'scheduled'
            ? self::withScheduledDuty($query, $request->user(), $authorizer)
            : $query;
    }

    /** @return Builder<User> */
    public static function scheduledFor(User $actor, ModelAuthorizer $authorizer): Builder
    {
        return self::withScheduledDuty(User::query(), $actor, $authorizer);
    }

    /** @param Builder<User> $query */
    private static function withScheduledDuty(Builder $query, User $actor, ModelAuthorizer $authorizer): Builder
    {
        $tenantIds = $actor->isSuperAdmin() || $authorizer->allows($actor, 'users.read.*')
            ? null
            : $authorizer->tenants($actor, 'users.read.padalinys')->pluck('id');

        return $query->whereHas('duties', function (Builder $dutyQuery) use ($tenantIds): void {
            $dutyQuery->whereDate('dutiables.start_date', '>', now()->toDateString())
                ->where(fn (Builder $dates) => $dates->whereNull('dutiables.end_date')
                    ->orWhereDate('dutiables.end_date', '>=', today()));

            if ($tenantIds !== null) {
                $dutyQuery->whereHas('institution', fn (Builder $institution) => $institution->whereIn('tenant_id', $tenantIds));
            }
        });
    }
}
