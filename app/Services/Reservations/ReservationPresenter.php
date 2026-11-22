<?php

namespace App\Services\Reservations;

use App\Actions\LoadReservationResourceApprovals;
use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Models\Reservation;
use App\Models\ReservationDraft;
use App\Models\ReservationDraftItem;
use App\Models\Resource;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\ResourceCapacityCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class ReservationPresenter
{
    public const array EAGER_LOADS = [
        'resources.tenant:id,shortname',
        'users:id,name,email,profile_photo_path',
    ];

    /**
     * @param  iterable<Reservation>  $reservations
     * @return list<array{
     *     id: string, name: string, description: string|null,
     *     start_time: Carbon, end_time: Carbon, created_at: Carbon, updated_at: Carbon,
     *     users: list<array{id: string, name: string, email: string|null, profile_photo_path: string|null}>,
     *     resources: list<array{
     *         id: string, name: string, tenant: array{id: int, shortname: string},
     *         pivot: array{
     *             id: int, reservation_id: string, resource_id: string,
     *             start_time: Carbon|null, end_time: Carbon|null, returned_at: Carbon|null, updated_at: Carbon,
     *             quantity: int, state: string, state_properties: array{tagType: string, description: string},
     *             approvable: bool, backtrackable: bool, cancellable: bool
     *         }
     *     }>
     * }>
     */
    public static function tableRows(iterable $reservations, User $user, ModelAuthorizer $authorizer): array
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
                    ->map(fn (Resource $resource): array => self::tableResourceRow($resource, $managedTenantIds, $isParticipant))
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, int|string>  $managedTenantIds
     * @return array{
     *     id: string, name: string, tenant: array{id: int, shortname: string},
     *     pivot: array{
     *         id: int, reservation_id: string, resource_id: string,
     *         start_time: Carbon|null, end_time: Carbon|null, returned_at: Carbon|null, updated_at: Carbon,
     *         quantity: int, state: string, state_properties: array{tagType: string, description: string},
     *         approvable: bool, backtrackable: bool, cancellable: bool
     *     }
     * }
     */
    private static function tableResourceRow(Resource $resource, Collection $managedTenantIds, bool $isParticipant): array
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

    /**
     * An empty draft is still returned: it means the user started a reservation, and the resource
     * list then offers "Pridėti" instead of "Peržiūrėti".
     *
     * @return array{
     *     name: string|null, description: string|null, start_time: int|null, end_time: int|null,
     *     count: int, problemCount: int, expiresAt: string|null, ttlDays: int,
     *     items: list<array{
     *         id: int, resource_id: string, name: string|null, tenant_shortname: string|null,
     *         image_url: string|null, capacity: int, quantity: int, available: int|null,
     *         problem: 'removed'|'not_reservable'|'unavailable'|null
     *     }>
     * }|null
     */
    public static function cart(User $user): ?array
    {
        $draft = $user->reservationDraft()
            ->with(['items' => fn ($query) => $query->oldest('id'), 'items.resource.tenant:id,shortname', 'items.resource.media'])
            ->first();

        if ($draft === null) {
            return null;
        }

        $items = $draft->items->map(fn (ReservationDraftItem $item) => self::item($item, $draft))->values();

        return [
            'name' => $draft->name,
            'description' => $draft->description,
            'start_time' => $draft->start_time?->getTimestampMs(),
            'end_time' => $draft->end_time?->getTimestampMs(),
            'count' => $items->count(),
            'problemCount' => $items->whereNotNull('problem')->count(),
            'expiresAt' => $draft->updated_at?->copy()->addDays((int) config('vusa.reservation_draft_ttl_days'))->toIso8601String(),
            'ttlDays' => (int) config('vusa.reservation_draft_ttl_days'),
            'items' => $items->all(),
        ];
    }

    /**
     * Omit empty drafts on Pradžia so there is no link back to an untouched checkout.
     *
     * @return array{name: string|null, count: int, start_time: int|null, end_time: int|null}|null
     */
    public static function cartSummary(User $user): ?array
    {
        $draft = $user->reservationDraft()->withCount('items')->first();

        if ($draft === null || ($draft->items_count === 0 && ! filled($draft->name) && ! filled($draft->description))) {
            return null;
        }

        return [
            'name' => $draft->name,
            'count' => (int) $draft->items_count,
            'start_time' => $draft->start_time?->getTimestampMs(),
            'end_time' => $draft->end_time?->getTimestampMs(),
        ];
    }

    /**
     * @return array{
     *     id: int, resource_id: string, name: string|null, tenant_shortname: string|null,
     *     image_url: string|null, capacity: int, quantity: int, available: int|null,
     *     problem: 'removed'|'not_reservable'|'unavailable'|null
     * }
     */
    private static function item(ReservationDraftItem $item, ReservationDraft $draft): array
    {
        /** @var resource|null $resource */
        $resource = $item->resource;

        if ($resource === null || $resource->trashed()) {
            return [
                'id' => $item->id,
                'resource_id' => $item->resource_id,
                'name' => $resource?->name,
                'tenant_shortname' => $resource?->tenant->shortname,
                'image_url' => null,
                'capacity' => 0,
                'quantity' => $item->quantity,
                'available' => 0,
                'problem' => 'removed',
            ];
        }

        $available = $draft->hasPeriod()
            ? ResourceCapacityCalculator::available($resource, $draft->start_time, $draft->end_time)
            : null;

        $problem = match (true) {
            ! $resource->is_reservable => 'not_reservable',
            $available !== null && $item->quantity > $available => 'unavailable',
            $available === null && $item->quantity > $resource->capacity => 'unavailable',
            default => null,
        };

        return [
            'id' => $item->id,
            'resource_id' => $resource->id,
            'name' => $resource->name,
            'tenant_shortname' => $resource->tenant->shortname,
            'image_url' => $resource->getFirstMediaUrl('images') ?: null,
            'capacity' => (int) $resource->capacity,
            'quantity' => $item->quantity,
            'available' => $available === null ? null : max(0, $available),
            'problem' => $problem,
        ];
    }
}
