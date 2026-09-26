<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Actions\SerializeReservationCart;
use App\Actions\SerializeResourceAvailability;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\StagingProtection;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ResourceController extends AdminController
{
    use HandlesSoftDeletes;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * The collection reads from Typesense (its scoped key carries the authorization), so the page
     * needs no rows from us.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Resource::class);

        return $this->inertiaResponse('Admin/Reservations/IndexResource', [
            'reservationCart' => SerializeReservationCart::execute(request()->user()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Resource::class);

        return $this->inertiaResponse('Admin/Reservations/CreateResource', [
            'assignableTenants' => GetTenantsForUpserts::execute('resources.create.padalinys', $this->authorizer),
            'categories' => ResourceCategory::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request)
    {
        $this->handleAuthorization('create', Resource::class);

        if ($request->validated('media') !== []) {
            StagingProtection::ensureFilesAreWritable();
        }

        $resource = new Resource;

        $resource->fill($request->safe()->except('media'));
        $resource->save();

        $resource = $resource->fresh();

        foreach ($request->validated('media') as $image) {
            $resource->addMedia($image['file'])->toMediaCollection('images');
        }

        return redirect()->route('resources.index')->with('success', $this->entityMessage('created', 'resource'));
    }

    /**
     * The resource record: what it is, whether it is free, who has it and when it is booked next.
     */
    public function show(Resource $resource)
    {
        $this->handleAuthorization('view', $resource);

        $user = request()->user();
        $now = now();

        $resource->load(['tenant:id,shortname', 'category', 'media']);

        $active = $resource->active_reservations()->orderByPivot('start_time')->get();

        $row = fn (Reservation $reservation): array => $this->reservationRow($reservation, $user, $now);

        return $this->inertiaResponse('Admin/Reservations/ShowResource', [
            'resource' => [
                'id' => $resource->id,
                'name' => $resource->name,
                'description' => $resource->description,
                'location' => $resource->location,
                'identifier' => $resource->identifier,
                'capacity' => (int) $resource->capacity,
                'is_reservable' => (bool) $resource->is_reservable,
                'tenant' => $resource->tenant->only(['id', 'shortname']),
                'category' => $resource->category?->name,
                'images' => $resource->getMedia('images')->map(fn ($image) => $image->getUrl())->values(),
            ],
            'availableNow' => max(0, SerializeResourceAvailability::available($resource, $now, $now->copy()->addMinute())),
            'currentLoans' => $active->filter(fn (Reservation $reservation) => $reservation->pivot->start_time?->lte($now))->map($row)->values(),
            'upcoming' => $active->filter(fn (Reservation $reservation) => $reservation->pivot->start_time?->gt($now))->take(10)->map($row)->values(),
            'managers' => $resource->managers()->map(fn ($manager) => [
                'id' => (string) $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'profile_photo_path' => $manager->profile_photo_path,
            ])->values(),
            'history' => Inertia::defer(fn () => $resource->reservations()
                ->wherePivotIn('state', ['returned', 'rejected', 'cancelled'])
                ->orderByPivot('end_time', 'desc')
                ->limit(50)
                ->get()
                ->map($row)
                ->values()),
            'reservationCart' => SerializeReservationCart::execute($user),
            'can' => [
                'update' => $user->can('update', $resource),
                'delete' => $user->can('delete', $resource),
                'reserve' => $resource->is_reservable && $user->can('create', Reservation::class),
            ],
        ]);
    }

    /**
     * Someone else's reservation shows only its period and quantity: availability is public to
     * every admin user, the reservations behind it are not.
     *
     * @return array<string, mixed>
     */
    private function reservationRow(Reservation $reservation, User $user, Carbon $now): array
    {
        $visible = $user->can('view', $reservation);
        $state = (string) $reservation->pivot->state;
        $endTime = $reservation->pivot->end_time;

        return [
            'id' => (int) $reservation->pivot->id,
            'reservation_id' => $visible ? (string) $reservation->id : null,
            'name' => $visible ? $reservation->name : null,
            'href' => $visible ? route('reservations.show', $reservation->id) : null,
            'quantity' => (int) $reservation->pivot->quantity,
            'state' => $state,
            'start_time' => $reservation->pivot->start_time?->getTimestampMs(),
            'end_time' => $endTime?->getTimestampMs(),
            'overdue' => in_array($state, ['created', 'reserved', 'lent'], true) && $endTime?->lt($now) === true,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resource $resource)
    {
        $this->handleAuthorization('update', $resource);

        $user = request()->user();
        $now = now();

        return $this->inertiaResponse('Admin/Reservations/EditResource', [
            // The full history lives on the resource page; the form only shows what is newest.
            'recentReservations' => $resource->reservations()
                ->orderByPivot('start_time', 'desc')
                ->limit(5)
                ->get()
                ->map(fn (Reservation $reservation): array => $this->reservationRow($reservation, $user, $now))
                ->values(),
            'resource' => $resource->toFullArray()
                + ['media' => $resource->getMedia('images')->map(fn ($image) => [
                    'id' => $image->id,
                    'name' => $image->name,
                    'type' => $image->mime_type,
                    'status' => 'finished',
                    'url' => $image->getUrl(),
                ]),
                ],
            'assignableTenants' => GetTenantsForUpserts::execute('resources.update.padalinys', $this->authorizer),
            'categories' => ResourceCategory::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $hasPendingMedia = collect($request->validated('media'))
            ->contains(fn (array $image): bool => $image['status'] === 'pending' && ($image['file'] ?? null) !== null);

        if ($hasPendingMedia) {
            StagingProtection::ensureFilesAreWritable();
        }

        $resource->fill($request->safe()->except('media'));
        $resource->save();

        // first, intersect existing media with id from request, delete one's that didn't match
        $resource->getMedia('images')
            ->filter(fn ($image) => ! in_array($image->id, array_column($request->validated('media'), 'id')))
            ->each(fn ($image) => $image->delete());

        // then add new media
        if ($request->validated('media')) {
            foreach ($request->validated('media') as $image) {
                if ($image['status'] === 'pending' && $image['file']) {
                    $resource->addMedia($image['file'])->toMediaCollection('images');
                }
            }
        }

        return back()
            ->with('success', $this->entityMessage('updated', 'resource'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        $this->handleAuthorization('delete', $resource);

        $resource->delete();

        return redirect()->route('resources.index')
            ->with('info', $this->entityMessage('deleted', 'resource'));
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Resource $resource): RedirectResponse
    {
        return $this->restoreModel($resource, $this->entityMessage('restored', 'resource'));
    }

    public function forceDelete(Resource $resource): RedirectResponse
    {
        return $this->forceDeleteModel($resource);
    }
}
