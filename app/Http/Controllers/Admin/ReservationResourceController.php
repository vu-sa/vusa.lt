<?php

namespace App\Http\Controllers\Admin;

use App\Actions\EnsureReservationCapacity;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreReservationResourceRequest;
use App\Http\Requests\UpdateReservationResourceRequest;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationResourceController extends AdminController
{
    /**
     * Redirect to the reservation show page. Separate reservation resource show pages are not needed.
     */
    public function show(ReservationResource $reservationResource): RedirectResponse
    {
        return redirect()->route('reservations.show', $reservationResource->reservation);
    }

    public function store(StoreReservationResourceRequest $request): RedirectResponse
    {
        $attributes = $request->resourceAttributes();

        DB::transaction(function () use ($request, $attributes): void {
            $this->ensureCapacity($attributes);
            $reservation = Reservation::query()->findOrFail($request->validated('reservation_id'));
            $this->handleAuthorization('update', $reservation);

            ReservationResource::create([...$attributes, 'reservation_id' => $reservation->id]);
        }, 3);

        return back()->with('success', $this->entityMessage('created', 'reservationResource'));
    }

    public function update(UpdateReservationResourceRequest $request, ReservationResource $reservationResource): RedirectResponse
    {
        $attributes = $request->resourceAttributes();

        DB::transaction(function () use ($reservationResource, $attributes): void {
            $locked = ReservationResource::query()->lockForUpdate()->findOrFail($reservationResource->id);
            Resource::query()->whereIn('id', [$locked->resource_id, $attributes['resource_id']])
                ->orderBy('id')->lockForUpdate()->get();

            if ((string) $locked->state !== 'created') {
                throw new AuthorizationException(__('reservations.messages.edit_requires_created'));
            }

            $this->ensureCapacity($attributes, $locked->id);
            $this->handleAuthorization('update', $locked->reservation);
            $locked->update($attributes);
        }, 3);

        return back()->with('success', $this->entityMessage('updated', 'reservationResource'));
    }

    private function ensureCapacity(array $attributes, ?int $excludedReservationResourceId = null): void
    {
        try {
            EnsureReservationCapacity::execute(
                [['id' => $attributes['resource_id'], 'quantity' => $attributes['quantity']]],
                $attributes['start_time'],
                $attributes['end_time'],
                $excludedReservationResourceId,
            );
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $key => $messages) {
                $errors[$key === 'resources.0.id' ? 'resource_id' : 'quantity'] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }

    public function destroy(ReservationResource $reservationResource)
    {
        $this->handleAuthorization('delete', $reservationResource->reservation);

        $reservationResource->delete();

        return back()->with('info', $this->entityMessage('deleted', 'reservationResource'));
    }
}
