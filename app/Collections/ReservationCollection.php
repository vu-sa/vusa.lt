<?php

namespace App\Collections;

use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Collection;

/**
 * Custom collection for Reservation models with pivot-aware methods
 *
 * @extends Collection<int, Reservation>
 */
class ReservationCollection extends Collection
{
    /**
     * Get all pivot instances from the reservations
     *
     * @return Collection<int, ReservationResource>
     */
    public function getPivots(): Collection
    {
        return $this->map(function (Reservation $reservation) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return $pivot;
        });
    }

    /**
     * Filter reservations by pivot state
     *
     * @param  string|array<string>  $states
     * @return static
     */
    public function whereState(string|array $states): static
    {
        $states = is_array($states) ? $states : [$states];
        
        return $this->filter(function (Reservation $reservation) use ($states) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return in_array($pivot->state->value ?? (string)$pivot->state, $states);
        });
    }

    /**
     * Filter reservations that overlap with a given time range
     *
     * @param  \Carbon\Carbon  $start
     * @param  \Carbon\Carbon  $end
     * @return static
     */
    public function whereOverlaps(\Carbon\Carbon $start, \Carbon\Carbon $end): static
    {
        return $this->filter(function (Reservation $reservation) use ($start, $end) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            
            $pivotStart = \Carbon\Carbon::parse($pivot->start_time);
            $pivotEnd = \Carbon\Carbon::parse($pivot->end_time);
            
            return $pivotStart->isBefore($end) && $pivotEnd->isAfter($start);
        });
    }

    /**
     * Get the total quantity reserved across all reservations
     *
     * @return int
     */
    public function getTotalQuantity(): int
    {
        return $this->sum(function (Reservation $reservation) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return $pivot->quantity;
        });
    }

    /**
     * Get reservations that start before a given time
     *
     * @param  \Carbon\Carbon  $time
     * @return static
     */
    public function whereStartsBefore(\Carbon\Carbon $time): static
    {
        return $this->filter(function (Reservation $reservation) use ($time) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return \Carbon\Carbon::parse($pivot->start_time)->isBefore($time);
        });
    }

    /**
     * Get reservations that end after a given time
     *
     * @param  \Carbon\Carbon  $time
     * @return static
     */
    public function whereEndsAfter(\Carbon\Carbon $time): static
    {
        return $this->filter(function (Reservation $reservation) use ($time) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return \Carbon\Carbon::parse($pivot->end_time)->isAfter($time);
        });
    }

    /**
     * Sort reservations by pivot start time
     *
     * @param  bool  $descending
     * @return static
     */
    public function sortByStartTime(bool $descending = false): static
    {
        return $this->sortBy(function (Reservation $reservation) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return \Carbon\Carbon::parse($pivot->start_time);
        }, SORT_REGULAR, $descending);
    }

    /**
     * Sort reservations by pivot end time
     *
     * @param  bool  $descending
     * @return static
     */
    public function sortByEndTime(bool $descending = false): static
    {
        return $this->sortBy(function (Reservation $reservation) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return \Carbon\Carbon::parse($pivot->end_time);
        }, SORT_REGULAR, $descending);
    }

    /**
     * Group reservations by their pivot state
     *
     * @return \Illuminate\Support\Collection<int, ReservationCollection>
     */
    public function groupByState(): \Illuminate\Support\Collection
    {
        return $this->groupBy(function (Reservation $reservation) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            return $pivot->state->value ?? (string)$pivot->state;
        })->map(function ($group) {
            return new ReservationCollection($group->all());
        });
    }

    /**
     * Get only the essential reservation data with pivot info (optimized for API responses)
     *
     * @return array<int, array<string, mixed>>
     */
    public function toOptimizedArray(): array
    {
        return $this->map(function (Reservation $reservation) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;
            
            return [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'description' => $reservation->description,
                'pivot' => [
                    'start_time' => $pivot->start_time,
                    'end_time' => $pivot->end_time,
                    'quantity' => $pivot->quantity,
                    'state' => $pivot->state->value ?? (string)$pivot->state,
                ],
            ];
        })->all();
    }
}