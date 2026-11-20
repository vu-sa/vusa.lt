<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\ReservationCart\DestroyReservationCartRequest;
use App\Http\Requests\ReservationCart\StoreReservationCartItemRequest;
use App\Http\Requests\ReservationCart\UpdateReservationCartItemRequest;
use App\Http\Requests\ReservationCart\UpdateReservationCartRequest;
use App\Models\Reservation;
use App\Models\ReservationDraft;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

/**
 * The acting user's reservation cart. Every endpoint resolves the draft from the session user —
 * there is no draft id in the URL to tamper with.
 */
class ReservationCartController extends AdminController
{
    #[Authorize('create', Reservation::class)]
    public function update(UpdateReservationCartRequest $request): RedirectResponse
    {
        $draft = $this->draftFor($request->user());

        // touch() saves too, so an unchanged period still extends the draft's lifetime.
        $draft->fill($request->validated())->touch();

        return back();
    }

    #[Authorize('create', Reservation::class)]
    public function destroy(DestroyReservationCartRequest $request): RedirectResponse
    {
        $request->user()->reservationDraft()->delete();

        return back()->with('info', __('reservations.cart.cleared'));
    }

    /**
     * Adding what is already in the cart sets its quantity rather than duplicating the row.
     */
    #[Authorize('create', Reservation::class)]
    public function storeItem(StoreReservationCartItemRequest $request): RedirectResponse
    {
        $this->draftFor($request->user())->items()->updateOrCreate(
            ['resource_id' => $request->validated('resource_id')],
            ['quantity' => $request->validated('quantity', 1)],
        );

        return back();
    }

    #[Authorize('create', Reservation::class)]
    public function updateItem(UpdateReservationCartItemRequest $request, Resource $resource): RedirectResponse
    {
        $item = $request->user()->reservationDraft?->items()->where('resource_id', $resource->id)->first();

        abort_if($item === null, 404);

        $item->update(['quantity' => $request->validated('quantity')]);

        return back();
    }

    #[Authorize('create', Reservation::class)]
    public function destroyItem(DestroyReservationCartRequest $request, Resource $resource): RedirectResponse
    {
        $request->user()->reservationDraft?->items()->where('resource_id', $resource->id)->first()?->delete();

        return back();
    }

    private function draftFor(User $user): ReservationDraft
    {
        return $user->reservationDraft()->firstOrCreate();
    }
}
