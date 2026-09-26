<?php

namespace App\Actions;

use App\Models\Reservation;
use App\Models\ReservationDraft;
use App\Models\ReservationDraftItem;
use App\Models\Resource;
use App\Notifications\ReservationDraftItemTakenNotification;
use Illuminate\Support\Collection;

/**
 * After a reservation is stored, tell other users whose unfinished reservation holds one of its
 * resources — for an overlapping time — when there is no longer enough left for them.
 */
class NotifyAffectedReservationDrafts
{
    public static function execute(Reservation $reservation): void
    {
        /** @var Collection<string, int> $reservedQuantities */
        $reservedQuantities = $reservation->resources
            ->mapWithKeys(fn (Resource $resource) => [(string) $resource->id => (int) $resource->pivot?->quantity]);
        $resourceIds = $reservedQuantities->keys();

        if ($resourceIds->isEmpty()) {
            return;
        }

        $drafts = ReservationDraft::query()
            ->whereNotIn('user_id', $reservation->users()->pluck('users.id'))
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('start_time', '<', $reservation->end_time)
            ->where('end_time', '>', $reservation->start_time)
            ->whereHas('items', fn ($query) => $query->whereIn('resource_id', $resourceIds))
            ->with(['user', 'items' => fn ($query) => $query->whereIn('resource_id', $resourceIds), 'items.resource'])
            ->get();

        foreach ($drafts as $draft) {
            $taken = $draft->items
                ->filter(function (ReservationDraftItem $item) use ($draft, $reservedQuantities): bool {
                    if ($item->resource === null) {
                        return false;
                    }

                    $available = SerializeResourceAvailability::available($item->resource, $draft->start_time, $draft->end_time);

                    // Only this reservation's doing: the item fitted before it took its share.
                    return $item->quantity > $available
                        && $item->quantity <= $available + $reservedQuantities->get((string) $item->resource_id, 0);
                })
                ->map(fn (ReservationDraftItem $item) => (string) $item->resource?->name)
                ->values()
                ->all();

            if ($taken === []) {
                continue;
            }

            $draft->user->notify(new ReservationDraftItemTakenNotification(
                $taken,
                $draft->start_time?->format('Y-m-d H:i').' – '.$draft->end_time?->format('Y-m-d H:i'),
            ));
        }
    }
}
