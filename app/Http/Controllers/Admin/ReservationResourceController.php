<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Pivots\ReservationResource;
use Inertia\Inertia;

class ReservationResourceController extends LaravelResourceController
{
    public function show(ReservationResource $reservationResource) {
        $this->authorize('view', [$reservationResource, $this->authorizer]);

        return Inertia::render('Admin/ReservationResource/Show', [
            'reservationResource' => $reservationResource->load('resource', 'reservation'),
        ]);
    }

    public function destroy(ReservationResource $reservationResource) {
        $this->authorize('delete', [$reservationResource, $this->authorizer]);

        $reservationResource->delete();

        return back()->with('success', 'Išteklius rezervacijoje sėkmingai ištrintas.');
    }
}
