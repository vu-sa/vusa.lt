<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;

class ReservationsDashboardController extends AdminController
{
    public function __construct(
        public Authorizer $authorizer,
    ) {}

    public function reservations()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);

        /**
         * Resolved once: ReservationResource::canBeApprovedBy() applies the same rule per pivot,
         * which would re-resolve the authorizer — and query the reservation's users — for every row.
         */
        $managedTenantIds = $this->authorizer
            ->tenants($user, config('permission.resource_managership_indicating_permission'))
            ->pluck('id');

        $eagerLoads = [
            'resources.tenant:id,shortname',
            'users:id,name,email,profile_photo_path',
        ];

        $myReservations = $user->reservations()->with($eagerLoads)->get();

        $administeredReservations = Reservation::query()
            ->whereHas('resources', fn ($query) => $query->whereIn('resources.tenant_id', $managedTenantIds))
            ->with($eagerLoads)
            ->get();

        return $this->inertiaResponse('Admin/Dashboard/ShowReservations', [
            'myReservations' => $this->serializeReservations($myReservations, $managedTenantIds, $user),
            'administeredReservations' => $this->serializeReservations($administeredReservations, $managedTenantIds, $user),
            // KPI counts are derived on the client, from whatever the table is currently showing:
            // the tiles filter the table, so their numbers have to agree with the rows.
            'managedTenants' => Tenant::query()
                ->whereIn('id', $managedTenantIds)
                ->orderBy('shortname')
                ->get(['id', 'shortname']),
        ]);
    }

    /**
     * Flatten reservations for the dashboard table.
     *
     * Each resource carries its pivot plus two permission flags that mirror the two branches of
     * ReservationResource::canBeApprovedBy(), so the table never offers an action the server
     * would reject. Pivot fields are listed explicitly: the pivot model declares
     * $with = ['comments', 'approvals'], which has no business in a list payload.
     *
     * @param  SupportCollection<int, Reservation>  $reservations
     * @param  SupportCollection<int, int>  $managedTenantIds
     * @return list<array<string, mixed>>
     */
    private function serializeReservations(SupportCollection $reservations, SupportCollection $managedTenantIds, User $user): array
    {
        return $reservations->map(function (Reservation $reservation) use ($managedTenantIds, $user) {
            $isParticipant = $reservation->users->contains('id', $user->id);

            return [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'description' => $reservation->description,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'created_at' => $reservation->created_at,
                'users' => $reservation->users
                    ->map(fn (User $manager) => $this->serializeReservationUser($manager))
                    ->values()
                    ->all(),
                'resources' => $reservation->resources
                    ->map(fn (Resource $resource) => $this->serializeReservationResource($resource, $managedTenantIds, $isParticipant))
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * Serialize one reserved resource, with the pivot the table acts on.
     *
     * @param  SupportCollection<int, int>  $managedTenantIds
     * @return array<string, mixed>
     */
    private function serializeReservationResource(Resource $resource, SupportCollection $managedTenantIds, bool $isParticipant): array
    {
        $pivot = $resource->pivot;
        $state = $pivot->state->getValue();

        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'tenant' => [
                'id' => $resource->tenant->id,
                'shortname' => $resource->tenant->shortname,
            ],
            'pivot' => [
                'id' => $pivot->id,
                'reservation_id' => $pivot->reservation_id,
                'resource_id' => $pivot->resource_id,
                'start_time' => $pivot->start_time,
                'end_time' => $pivot->end_time,
                'returned_at' => $pivot->returned_at,
                // Fallback for returned_at, which is only stamped on items returned
                // since it started being written.
                'updated_at' => $pivot->updated_at,
                'quantity' => $pivot->quantity,
                'state' => $state,
                'state_properties' => $pivot->state_properties,
                'approvable' => $managedTenantIds->contains($resource->tenant_id),
                'cancellable' => $isParticipant && in_array($state, ['created', 'reserved'], true),
            ],
        ];
    }

    /**
     * Serialize a single user attached to a reservation.
     *
     * @return array<string, mixed>
     */
    private function serializeReservationUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
        ];
    }
}
