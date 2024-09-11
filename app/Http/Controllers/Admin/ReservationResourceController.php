<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreReservationResourceRequest;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservationResourceController extends LaravelResourceController
{
    /**
     * Redirect to the reservation show page. Separate reservation resource show pages are not needed.
     *
     * @param ReservationResource reservationResource
     * @return RedirectResponse
     */
    public function show(ReservationResource $reservationResource): RedirectResponse
    {
        return redirect()->route('reservations.show', $reservationResource->reservation);
    }

    public function store(StoreReservationResourceRequest $request)
    {
        $reservationResource = new ReservationResource;

        $reservationResource->fill($request->validated());
        $reservationResource->save();

        return back()->with('success', trans_choice('messages.created', 1, ['model' => trans_choice('entities.reservation_resource.model', 1)]));
    }

    /**
     * Only used to update the amount of a reservation resource.
     *
     * @param Request request
     * @param ReservationResource reservationResource
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ReservationResource $reservationResource): RedirectResponse
    {
        $reservation = Reservation::query()->find($reservationResource->reservation_id);

        $this->authorize('update', [Reservation::class, $reservation, $this->authorizer]);

        $reservationResource->resource_id = $request->resource_id;
        $reservationResource->quantity = $request->quantity;

        $reservationResource->save();

        return back()->with('success', trans_choice('messages.updated', 1, ['model' => trans_choice('entities.reservation_resource.model', 1)]));
    }

    public function destroy(ReservationResource $reservationResource)
    {
        $this->authorize('delete', [Reservation::class, $reservationResource->reservation, $this->authorizer]);

        $reservationResource->delete();

        return back()->with('info', trans_choice('messages.deleted', 1, ['model' => trans_choice('entities.reservation_resource.model', 1)]));
    }
}
