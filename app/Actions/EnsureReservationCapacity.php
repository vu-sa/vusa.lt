<?php

namespace App\Actions;

use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Refuses a reservation that asks for more than is free. Call it inside the storing transaction:
 * it locks the resource rows so two checkouts cannot both take the last unit.
 */
class EnsureReservationCapacity
{
    /**
     * @param  array<int, array{id: string, quantity: int}>  $lines  Keyed as submitted, so errors land on the right line.
     *
     * @throws ValidationException
     */
    public static function execute(array $lines, Carbon $start, Carbon $end): void
    {
        $resources = Resource::query()
            ->whereIn('id', array_column($lines, 'id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($lines as $index => $line) {
            $resource = $resources->get($line['id']);

            if ($resource === null || ! $resource->is_reservable) {
                $errors["resources.{$index}.id"] = __('reservations.cart.not_reservable_error');

                continue;
            }

            $available = max(0, SerializeResourceAvailability::available($resource, $start, $end));

            if ((int) $line['quantity'] > $available) {
                $errors["resources.{$index}.quantity"] = __('reservations.cart.capacity_exceeded', [
                    'resource' => $resource->name,
                    'count' => $available,
                ]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
