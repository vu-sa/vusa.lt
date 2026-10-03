<?php

use App\Models\Reservation;
use App\Models\ReservationDraft;
use App\Models\ReservationDraftItem;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ReservationDraftItemTakenNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->resource = Resource::factory()->for($this->tenant)->create(['capacity' => 2, 'is_reservable' => true]);

    $this->start = now()->addDays(2)->setTime(9, 0);
    $this->end = now()->addDays(3)->setTime(17, 0);
});

/**
 * Books $quantity of $resource for the cart's test period under a reservation owned by someone else.
 */
function reserveForOthers(Resource $resource, int $quantity, $start, $end): Reservation
{
    $reservation = Reservation::factory()->create(['start_time' => $start, 'end_time' => $end]);
    $reservation->users()->attach(User::factory()->create()->id);
    $reservation->resources()->attach($resource->id, [
        'quantity' => $quantity,
        'start_time' => $start,
        'end_time' => $end,
        'state' => 'reserved',
    ]);

    return $reservation;
}

describe('cart items', function (): void {
    test('adding an item creates the draft, adding it again sets the quantity', function (): void {
        asUser($this->user)->post(route('reservationCart.items.store'), ['resource_id' => $this->resource->id])
            ->assertRedirect();
        asUser($this->user)->post(route('reservationCart.items.store'), ['resource_id' => $this->resource->id, 'quantity' => 2])
            ->assertRedirect();

        $draft = $this->user->reservationDraft()->sole();

        expect($draft->items)->toHaveCount(1)
            ->and($draft->items->first()->quantity)->toBe(2);
    });

    test('quantity can be changed and an item removed', function (): void {
        $draft = ReservationDraft::factory()->for($this->user)->create();
        ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create();

        asUser($this->user)->patch(route('reservationCart.items.update', $this->resource), ['quantity' => 2])->assertRedirect();
        expect($draft->items()->sole()->quantity)->toBe(2);

        asUser($this->user)->delete(route('reservationCart.items.destroy', $this->resource))->assertRedirect();
        expect($draft->items()->count())->toBe(0);
    });

    test('a resource that is not reservable or is trashed cannot be added', function (): void {
        $notReservable = Resource::factory()->for($this->tenant)->create(['is_reservable' => false]);
        $trashed = Resource::factory()->for($this->tenant)->create();
        $trashed->delete();

        asUser($this->user)->post(route('reservationCart.items.store'), ['resource_id' => $notReservable->id])
            ->assertSessionHasErrors('resource_id');
        asUser($this->user)->post(route('reservationCart.items.store'), ['resource_id' => $trashed->id])
            ->assertSessionHasErrors('resource_id');

        expect(ReservationDraftItem::query()->count())->toBe(0);
    });

    test('a user only ever touches their own draft', function (): void {
        $other = makeUser($this->tenant);
        $otherDraft = ReservationDraft::factory()->for($other)->create();
        ReservationDraftItem::factory()->for($otherDraft, 'draft')->for($this->resource)->create();

        asUser($this->user)->delete(route('reservationCart.items.destroy', $this->resource))->assertRedirect();
        asUser($this->user)->delete(route('reservationCart.destroy'))->assertRedirect();

        expect($otherDraft->items()->count())->toBe(1)
            ->and(ReservationDraft::query()->whereKey($otherDraft->id)->exists())->toBeTrue();
    });

    test('guests cannot use the cart', function (): void {
        $this->post(route('reservationCart.items.store'), ['resource_id' => $this->resource->id])
            ->assertRedirect(route('login'));

        expect(ReservationDraft::query()->count())->toBe(0);
    });

    test('clearing the cart deletes the draft and its items', function (): void {
        $draft = ReservationDraft::factory()->for($this->user)->create();
        ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create();

        asUser($this->user)->delete(route('reservationCart.destroy'))->assertRedirect();

        expect(ReservationDraft::query()->count())->toBe(0)
            ->and(ReservationDraftItem::query()->count())->toBe(0);
    });
});

describe('an empty draft', function (): void {
    test('still counts as a started reservation on the resource list, but stays off Pradžia', function (): void {
        asUser($this->user)->get(route('resources.index'))
            ->assertInertia(fn (Assert $page) => $page->where('reservationCart', null));

        asUser($this->user)->put(route('reservationCart.update'), ['name' => null, 'description' => null])->assertRedirect();

        asUser($this->user)->get(route('resources.index'))
            ->assertInertia(fn (Assert $page) => $page->where('reservationCart.count', 0));

        asUser($this->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('reservationDraft', null));
    });
});

describe('cart period and availability', function (): void {
    test('changing the period keeps the items and flags what no longer fits', function (): void {
        $draft = ReservationDraft::factory()->for($this->user)->create();
        ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create(['quantity' => 2]);
        reserveForOthers($this->resource, 1, $this->start, $this->end);

        asUser($this->user)->put(route('reservationCart.update'), [
            'start_time' => $this->start->getTimestampMs(),
            'end_time' => $this->end->getTimestampMs(),
        ])->assertRedirect();

        expect($draft->items()->count())->toBe(1);

        asUser($this->user)->get(route('resources.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reservationCart.count', 1)
                ->where('reservationCart.problemCount', 1)
                ->where('reservationCart.items.0.problem', 'unavailable')
                ->where('reservationCart.items.0.available', 1)
            );
    });

    test('the period must end after it starts', function (): void {
        asUser($this->user)->put(route('reservationCart.update'), [
            'start_time' => $this->end->getTimestampMs(),
            'end_time' => $this->start->getTimestampMs(),
        ])->assertSessionHasErrors('end_time');
    });
});

describe('checkout', function (): void {
    test('submitting the reservation consumes the draft', function (): void {
        $draft = ReservationDraft::factory()->for($this->user)->withPeriod($this->start, $this->end)->create();
        ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create();

        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Renginys',
            'description' => 'Reikia renginiui',
            'start_time' => $this->start->getTimestampMs(),
            'end_time' => $this->end->getTimestampMs(),
            'resources' => [['id' => $this->resource->id, 'quantity' => 1]],
        ])->assertRedirect();

        expect(Reservation::where('name', 'Renginys')->exists())->toBeTrue()
            ->and(ReservationDraft::query()->count())->toBe(0);
    });

    test('a quantity above what is free is refused and the draft survives', function (): void {
        $draft = ReservationDraft::factory()->for($this->user)->withPeriod($this->start, $this->end)->create();
        ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create(['quantity' => 2]);
        reserveForOthers($this->resource, 1, $this->start, $this->end);

        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Renginys',
            'description' => 'Reikia renginiui',
            'start_time' => $this->start->getTimestampMs(),
            'end_time' => $this->end->getTimestampMs(),
            'resources' => [['id' => $this->resource->id, 'quantity' => 2]],
        ])->assertSessionHasErrors('resources.0.quantity');

        expect(Reservation::where('name', 'Renginys')->exists())->toBeFalse()
            ->and(ReservationDraft::query()->count())->toBe(1);
    });

    test('the same resource cannot be listed twice', function (): void {
        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Renginys',
            'description' => 'Reikia renginiui',
            'start_time' => $this->start->getTimestampMs(),
            'end_time' => $this->end->getTimestampMs(),
            'resources' => [
                ['id' => $this->resource->id, 'quantity' => 1],
                ['id' => $this->resource->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('resources.0.id');
    });

    test('the checkout page reads the draft', function (): void {
        $draft = ReservationDraft::factory()->for($this->user)->withPeriod($this->start, $this->end)->create(['name' => 'Juodraštis']);
        ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create();

        asUser($this->user)->get(route('reservations.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reservations/CreateReservation')
                ->where('reservationCart.name', 'Juodraštis')
                ->where('reservationCart.start_time', $this->start->getTimestampMs())
                ->where('reservationCart.items.0.resource_id', $this->resource->id)
            );
    });
});

describe('someone else reserves an item in your cart', function (): void {
    test('the draft owner is notified once when the reservation leaves too little', function (): void {
        Notification::fake();

        $other = makeUser($this->tenant);
        $otherDraft = ReservationDraft::factory()->for($other)->withPeriod($this->start, $this->end)->create();
        ReservationDraftItem::factory()->for($otherDraft, 'draft')->for($this->resource)->create(['quantity' => 2]);

        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Renginys',
            'description' => 'Reikia renginiui',
            'start_time' => $this->start->getTimestampMs(),
            'end_time' => $this->end->getTimestampMs(),
            'resources' => [['id' => $this->resource->id, 'quantity' => 1]],
        ])->assertRedirect();

        Notification::assertSentToTimes($other, ReservationDraftItemTakenNotification::class, 1);
        Notification::assertNotSentTo($this->user, ReservationDraftItemTakenNotification::class);
    });

    test('no one is notified while their draft still fits', function (): void {
        Notification::fake();

        $other = makeUser($this->tenant);
        $otherDraft = ReservationDraft::factory()->for($other)->withPeriod($this->start, $this->end)->create();
        ReservationDraftItem::factory()->for($otherDraft, 'draft')->for($this->resource)->create(['quantity' => 1]);

        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Renginys',
            'description' => 'Reikia renginiui',
            'start_time' => $this->start->getTimestampMs(),
            'end_time' => $this->end->getTimestampMs(),
            'resources' => [['id' => $this->resource->id, 'quantity' => 1]],
        ])->assertRedirect();

        Notification::assertNotSentTo($other, ReservationDraftItemTakenNotification::class);
    });

    test('a draft that already did not fit is not notified again', function (): void {
        Notification::fake();
        $this->resource->update(['capacity' => 3]);
        reserveForOthers($this->resource, 2, $this->start, $this->end);

        $other = makeUser($this->tenant);
        $otherDraft = ReservationDraft::factory()->for($other)->withPeriod($this->start, $this->end)->create();
        ReservationDraftItem::factory()->for($otherDraft, 'draft')->for($this->resource)->create(['quantity' => 3]);

        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Renginys',
            'description' => 'Reikia renginiui',
            'start_time' => $this->start->getTimestampMs(),
            'end_time' => $this->end->getTimestampMs(),
            'resources' => [['id' => $this->resource->id, 'quantity' => 1]],
        ])->assertRedirect();

        Notification::assertNotSentTo($other, ReservationDraftItemTakenNotification::class);
    });
});

describe('pruning', function (): void {
    test('drafts untouched past the TTL are pruned with their items, fresh ones stay', function (): void {
        $stale = ReservationDraft::factory()->create();
        ReservationDraftItem::factory()->for($stale, 'draft')->for($this->resource)->create();
        ReservationDraft::query()->whereKey($stale->id)->update(['updated_at' => now()->subDays(config('vusa.reservation_draft_ttl_days') + 1)]);
        $fresh = ReservationDraft::factory()->create();

        $this->artisan('model:prune', ['--model' => [ReservationDraft::class]])->assertSuccessful();

        expect(ReservationDraft::query()->pluck('id')->all())->toBe([$fresh->id])
            ->and(ReservationDraftItem::query()->count())->toBe(0);
    });

    test('changing an item keeps the draft alive', function (): void {
        $draft = ReservationDraft::factory()->for($this->user)->create();
        $item = ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create();
        ReservationDraft::query()->whereKey($draft->id)->update(['updated_at' => now()->subDays(config('vusa.reservation_draft_ttl_days') + 1)]);

        $item->update(['quantity' => 2]);

        expect($draft->fresh()->updated_at->isToday())->toBeTrue();
    });
});
