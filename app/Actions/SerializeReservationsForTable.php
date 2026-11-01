<?php

namespace App\Actions;

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;

/** Shared reservation rows and action flags for collections, API pages and overviews. */
class SerializeReservationsForTable
{
    /** Eager loads the payload reads; callers add them to their query. */
    public const array EAGER_LOADS = [
        'resources.tenant:id,shortname',
        'users:id,name,email,profile_photo_path',
    ];

    /**
     * @param  iterable<Reservation>  $reservations
     * @return list<array<string, mixed>>
     */
    public static function execute(iterable $reservations, User $user, ModelAuthorizer $authorizer): array
    {
        $reservations = collect($reservations);
        LoadReservationResourceApprovals::execute($reservations, [
            'approvals:id,approvable_type,approvable_id,decision,reverted_at',
        ]);

        // Resolve once instead of repeating authorization queries for every pivot.
        $managedTenantIds = $authorizer
            ->tenants($user, config('permission.resource_managership_indicating_permission'))
            ->pluck('id');

        return $reservations->map(function (Reservation $reservation) use ($managedTenantIds, $user): array {
            $isParticipant = $reservation->users->contains('id', $user->id);

            return [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'description' => $reservation->description,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
                'users' => $reservation->users
                    ->map(fn (User $manager): array => [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                        'profile_photo_path' => $manager->profile_photo_path,
                    ])
                    ->values()
                    ->all(),
                'resources' => $reservation->resources
                    ->map(fn (Resource $resource): array => self::serializeResource($resource, $managedTenantIds, $isParticipant))
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, int|string>  $managedTenantIds
     * @return array<string, mixed>
     */
    private static function serializeResource(Resource $resource, Collection $managedTenantIds, bool $isParticipant): array
    {
        $pivot = $resource->pivot;
        $state = $pivot->state->getValue();
        $managed = $managedTenantIds->contains($resource->tenant_id);

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
                // Fallback for returned_at, which is only stamped on items returned since it started being written.
                'updated_at' => $pivot->updated_at,
                'quantity' => $pivot->quantity,
                'state' => $state,
                'state_properties' => $pivot->state_properties,
                'approvable' => $managed,
                'backtrackable' => $managed
                    && in_array($state, ['reserved', 'lent', 'returned'], true)
                    && $pivot->approvals->contains(
                        fn (Approval $approval): bool => $approval->decision === ApprovalDecision::Approved
                            && $approval->reverted_at === null
                    ),
                'cancellable' => $isParticipant && in_array($state, ['created', 'reserved'], true),
            ],
        ];
    }
}
