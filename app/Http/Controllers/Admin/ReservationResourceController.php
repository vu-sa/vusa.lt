<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreReservationResourceRequest;
use App\Models\Pivots\ReservationResource;

class ReservationResourceController extends LaravelResourceController
{
    public function show(ReservationResource $reservationResource)
    {
        return redirect()->route('reservations.show', $reservationResource->reservation);
    }

    public function store(StoreReservationResourceRequest $request)
    {
        $reservationResource = new ReservationResource();

        $reservationResource->fill($request->validated());
        $reservationResource->save();

        return back()->with('success', 'Išteklius rezervacijoje sėkmingai pridėtas.');
    }

    public function destroy(ReservationResource $reservationResource)
    {
        $this->authorize('delete', [Reservation::class, $reservationResource->reservation, $this->authorizer]);

        $reservationResource->delete();

        return back()->with('success', 'Išteklius rezervacijoje sėkmingai ištrintas.');
    }
}
