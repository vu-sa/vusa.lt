<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreReservationResourceRequest;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReservationResourceController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Redirect to the reservation show page. Separate reservation resource show pages are not needed.
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
     */
    public function update(Request $request, ReservationResource $reservationResource): RedirectResponse
    {
        $reservation = Reservation::query()->find($reservationResource->reservation_id);

        $this->handleAuthorization('update', $reservation);

        $reservationResource->start_time = Carbon::createFromTimestampMs($request->start_time, 'Europe/Vilnius');
        $reservationResource->end_time = Carbon::createFromTimestampMs($request->end_time, 'Europe/Vilnius');
        $reservationResource->resource_id = $request->resource_id;
        $reservationResource->quantity = $request->quantity;

        $reservationResource->save();

        return back()->with('success', trans_choice('messages.updated', 1, ['model' => trans_choice('entities.reservation_resource.model', 1)]));
    }

    public function destroy(ReservationResource $reservationResource)
    {
        $this->handleAuthorization('delete', $reservationResource->reservation);

        $reservationResource->delete();

        return back()->with('info', trans_choice('messages.deleted', 1, ['model' => trans_choice('entities.reservation_resource.model', 1)]));
    }
}
