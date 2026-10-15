<?php

namespace App\Actions;

use App\Models\ReservationDraft;
use App\Models\ReservationDraftItem;
use App\Models\User;

/**
 * The reservation cart payload shared by the resource browser, the resource record and checkout.
 *
 * The draft holds no capacity, so availability is recalculated on every read: an item someone
 * else reserved in the meantime comes back flagged with `problem: 'unavailable'` and the quantity
 * that is still free.
 */
class SerializeReservationCart
{
    /**
     * An empty draft is still returned: it means the user started a reservation, and the resource
     * list then offers "Pridėti" instead of "Peržiūrėti".
     *
     * @return array<string, mixed>|null Null when the user has no draft.
     */
    public static function execute(User $user): ?array
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
     * The cart without availability, for surfaces that only point back to it (Pradžia). A draft with
     * nothing in it yet is left out: there is nothing to come back to.
     *
     * @return array{name: string|null, count: int, start_time: int|null, end_time: int|null}|null
     */
    public static function summary(User $user): ?array
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
     * @return array<string, mixed>
     */
    private static function item(ReservationDraftItem $item, ReservationDraft $draft): array
    {
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
            ? SerializeResourceAvailability::available($resource, $draft->start_time, $draft->end_time)
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
