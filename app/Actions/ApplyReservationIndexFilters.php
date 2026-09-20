<?php

namespace App\Actions;

use App\Http\Requests\IndexReservationRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The reservation list's `scope`, `state` and `overdue` filters, shared by the Inertia page and its
 * API so a deep link from an overview and a later "Rodyti daugiau" page agree on the rows.
 *
 * Under `scope=administered` the state conditions apply to the items the user manages, not to the
 * whole reservation — a row's status is scoped the same way, and "Laukia sprendimo" must not surface
 * a reservation whose only pending item belongs to someone else.
 */
class ApplyReservationIndexFilters
{
    /**
     * @param  Builder<Reservation>  $query
     * @return Builder<Reservation>
     */
    public static function execute(Builder $query, IndexReservationRequest $request, User $user, ModelAuthorizer $authorizer): Builder
    {
        $scope = $request->getScope();
        $states = $request->getStates();
        $overdue = $request->getOverdue();

        if ($scope === 'mine') {
            $query->whereHas('users', fn (Builder|Relation $users) => $users->where('users.id', $user->id));
        }

        $managedTenantIds = $scope === 'administered'
            ? $authorizer->tenants($user, config('permission.resource_managership_indicating_permission'))->pluck('id')
            : null;

        if ($managedTenantIds === null && $states === [] && ! $overdue) {
            return $query;
        }

        return $query->whereHas('resources', function (Builder|Relation $resources) use ($managedTenantIds, $states, $overdue): void {
            if ($managedTenantIds !== null) {
                $resources->whereIn('resources.tenant_id', $managedTenantIds);
            }

            if ($states !== []) {
                $resources->whereIn('reservation_resource.state', $states);
            }

            if ($overdue) {
                $resources->whereIn('reservation_resource.state', ['created', 'reserved', 'lent'])
                    ->where('reservation_resource.end_time', '<', now());
            }
        });
    }
}
