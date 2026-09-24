<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Išteklių administratorius');

    $this->resource = Resource::factory()->for($this->tenant)->create([
        'identifier' => 'OLD-CODE',
        'is_reservable' => false,
    ]);
});

describe('update', function (): void {
    test('persists the identifier field', function (): void {
        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'identifier' => 'NEW-CODE',
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
        ])->assertRedirect();

        expect($this->resource->fresh()->identifier)->toBe('NEW-CODE');
    });

    test('persists is_reservable as a boolean', function (): void {
        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
        ])->assertRedirect();

        expect($this->resource->fresh()->is_reservable)->toBeTrue();
    });

    test('retains existing media and adds newly uploaded files', function (): void {
        $this->resource->addMedia(UploadedFile::fake()->image('existing.jpg'))->toMediaCollection('images');
        $existingMedia = $this->resource->fresh()->getMedia('images')->sole();

        $newImage = UploadedFile::fake()->image('new.jpg');

        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
            'media' => [
                ['id' => $existingMedia->id, 'status' => 'finished'],
                ['file' => $newImage, 'status' => 'pending'],
            ],
        ])->assertRedirect();

        $mediaNames = $this->resource->fresh()->getMedia('images')->pluck('name')->all();
        expect($mediaNames)->toHaveCount(2)
            ->and($mediaNames)->toContain($existingMedia->name);
    });

    test('accepts webp uploads (the format the browser-side compressor produces)', function (): void {
        $webpImage = UploadedFile::fake()->image('good-rep.webp', 100, 100);

        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
            'media' => [
                ['file' => $webpImage, 'status' => 'pending'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($this->resource->fresh()->getMedia('images'))->toHaveCount(1);
    });

    test('deletes media omitted from the submitted media array', function (): void {
        $this->resource->addMedia(UploadedFile::fake()->image('to-be-removed.jpg'))->toMediaCollection('images');

        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
            'media' => [],
        ])->assertRedirect();

        expect($this->resource->fresh()->getMedia('images'))->toBeEmpty();
    });
});

describe('index', function (): void {
    // The collection reads from Typesense (its scoped key carries the authorization), so the page
    // needs no rows — and, unlike the old redirect to the search tab, it renders here.
    test('renders the resources collection instead of redirecting to the search tab', function (): void {
        asUser($this->resourceManager)->get(route('resources.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Reservations/IndexResource'));
    });
});

describe('destroy', function (): void {
    test('returns to the resources collection with an info flash', function (): void {
        asUser($this->resourceManager)->delete(route('resources.destroy', $this->resource))
            ->assertRedirect(route('resources.index'))
            ->assertSessionHas('info');
    });

    test('the flash message survives to the page the browser actually renders', function (): void {
        asUser($this->resourceManager)->delete(route('resources.destroy', $this->resource))
            ->assertSessionHas('info');

        $expectedMessage = __('messages.deleted.m', ['model' => trans_choice('entities.resource.model', 1)]);

        asUser($this->resourceManager)->get(route('resources.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reservations/IndexResource')
                ->where('flash.info', $expectedMessage));
    });
});

describe('store', function (): void {
    test('returns to the resources collection with a success flash', function (): void {
        $expectedMessage = __('messages.created.m', ['model' => trans_choice('entities.resource.model', 1)]);

        asUser($this->resourceManager)->post(route('resources.store'), [
            'name' => ['lt' => 'Naujas', 'en' => 'New'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'location' => 'Room 1',
            'tenant_id' => $this->tenant->id,
            'capacity' => 1,
            'is_reservable' => true,
            'media' => [],
        ])
            ->assertRedirect(route('resources.index'))
            ->assertSessionHas('success');

        asUser($this->resourceManager)->get(route('resources.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reservations/IndexResource')
                ->where('flash.success', $expectedMessage));
    });
});

describe('tenant scoping of tenant_id', function (): void {
    /**
     * ResourcePolicy::create() inherits HasCommonChecks::create(), which only asks whether the
     * user holds resources.create.padalinys *somewhere*. The owning tenant arrives in the
     * payload, so it has to be constrained separately — otherwise a resource manager for one
     * padalinys can create (or move) resources inside another.
     */
    test('cannot create a resource in a tenant the user does not manage', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();

        asUser($this->resourceManager)->post(route('resources.store'), [
            'name' => ['lt' => 'Naujas', 'en' => 'New'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'location' => 'Room 1',
            'tenant_id' => $otherTenant->id,
            'capacity' => 1,
            'is_reservable' => true,
            'media' => [],
        ])->assertSessionHasErrors('tenant_id');

        expect(Resource::query()->where('tenant_id', $otherTenant->id)->exists())->toBeFalse();
    });

    test('cannot move an existing resource into a tenant the user does not manage', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();

        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $otherTenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
        ])->assertSessionHasErrors('tenant_id');

        expect($this->resource->fresh()->tenant_id)->toEqual($this->tenant->id);
    });
});

describe('show', function (): void {
    beforeEach(function (): void {
        $this->resource->update(['is_reservable' => true, 'capacity' => 3]);
        $this->member = makeUser($this->tenant);
    });

    /**
     * Attaches $resource to a fresh reservation owned by $owner.
     */
    function bookResource(Resource $resource, User $owner, $start, $end, string $state = 'reserved', int $quantity = 1): Reservation
    {
        $reservation = Reservation::factory()->create(['start_time' => $start, 'end_time' => $end]);
        $reservation->users()->attach($owner->id);
        $reservation->resources()->attach($resource->id, [
            'quantity' => $quantity,
            'start_time' => $start,
            'end_time' => $end,
            'state' => $state,
        ]);

        return $reservation;
    }

    test('renders the record with availability, loans and upcoming reservations', function (): void {
        bookResource($this->resource, $this->member, now()->subDay(), now()->addDay(), 'lent', 2);
        bookResource($this->resource, $this->member, now()->addWeek(), now()->addWeek()->addDay());

        asUser($this->member)->get(route('resources.show', $this->resource))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reservations/ShowResource')
                ->where('resource.id', $this->resource->id)
                ->where('availableNow', 1)
                ->has('currentLoans', 1)
                ->where('currentLoans.0.overdue', false)
                ->has('upcoming', 1)
                ->where('can.reserve', true)
                ->where('can.update', false)
                ->missing('history')
                ->loadDeferredProps(fn (Assert $reload) => $reload->has('history', 0))
            );
    });

    test('an unreturned item past its end time is flagged overdue', function (): void {
        bookResource($this->resource, $this->member, now()->subDays(3), now()->subDay(), 'lent');

        asUser($this->member)->get(route('resources.show', $this->resource))
            ->assertInertia(fn (Assert $page) => $page
                ->has('currentLoans', 1)
                ->where('currentLoans.0.overdue', true)
            );
    });

    test('someone else\'s reservation is shown without its name or link', function (): void {
        bookResource($this->resource, User::factory()->create(), now()->addWeek(), now()->addWeek()->addDay());

        asUser($this->member)->get(route('resources.show', $this->resource))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcoming.0.name', null)
                ->where('upcoming.0.href', null)
                ->where('upcoming.0.quantity', 1)
            );
    });

    test('a resource manager sees the reservation behind it and may edit', function (): void {
        $reservation = bookResource($this->resource, User::factory()->create(), now()->addWeek(), now()->addWeek()->addDay());

        asUser($this->resourceManager)->get(route('resources.show', $this->resource))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcoming.0.name', $reservation->name)
                ->where('upcoming.0.href', route('reservations.show', $reservation->id))
                ->where('can.update', true)
            );
    });

    test('a finished reservation appears in the deferred history', function (): void {
        bookResource($this->resource, $this->member, now()->subWeek(), now()->subDays(5), 'returned');

        asUser($this->member)->get(route('resources.show', $this->resource))
            ->assertInertia(fn (Assert $page) => $page
                ->has('currentLoans', 0)
                ->loadDeferredProps(fn (Assert $reload) => $reload->has('history', 1))
            );
    });
});

describe('edit', function (): void {
    test('shows the latest five reservations as resource-page rows, not the whole history', function (): void {
        $owner = User::factory()->create();

        foreach (range(1, 6) as $week) {
            $reservation = Reservation::factory()->create();
            $reservation->users()->attach($owner->id);
            $reservation->resources()->attach($this->resource->id, [
                'quantity' => 1,
                'start_time' => now()->addWeeks($week),
                'end_time' => now()->addWeeks($week)->addDay(),
                'state' => 'reserved',
            ]);
        }

        asUser($this->resourceManager)->get(route('resources.edit', $this->resource))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reservations/EditResource')
                ->has('recentReservations', 5)
                ->has('recentReservations.0.quantity')
                ->missing('resource.reservations')
            );
    });
});
