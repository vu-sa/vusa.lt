<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\ModelIndexer;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReservationController extends LaravelResourceController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware([HandlePrecognitiveRequests::class])->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', [Reservation::class, $this->authorizer]);

        $reservations = Reservation::search(request()->input('text'));

        $sorters = json_decode(base64_decode(request()->input('sorters')), true);

        $reservations = $reservations
        ->when(
            isset($sorters['name']) && $sorters['name'],
            function ($query) use ($sorters) {
                $query->orderBy('name', $sorters['name'] === 'descend' ? 'desc' : 'asc');
            })
        ->when(isset($sorters['start_time']) && $sorters['start_time'], function ($query) use ($sorters) {
            $query->orderBy('start_time', $sorters['start_time'] === 'descend' ? 'desc' : 'asc');
        })->when(isset($sorters['end_time']) && $sorters['end_time'], function ($query) use ($sorters) {
            $query->orderBy('end_time', $sorters['end_time'] === 'descend' ? 'desc' : 'asc');
        });

        $reservations = ModelIndexer::filterByAuthorized($reservations, $this->authorizer);

        return Inertia::render('Admin/Reservations/IndexReservation', [
            'reservations' => $reservations->get()->load('resources', 'users')->paginate(15)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Reservation::class, $this->authorizer]);

        $dateTimeRange = request()->input('dateTimeRange') ?? [
            'start' => now()->setTimeFromTimeString('09:00')->addDay()->format('Uv'),
            'end' => now()->setTimeFromTimeString('17:00')->addDays(5)->format('Uv'),
        ];

        // dateTimeRange to numeric
        $dateTimeRange = [
            'start' => intval($dateTimeRange['start']),
            'end' => intval($dateTimeRange['end']),
        ];

        return Inertia::render('Admin/Reservations/CreateReservation', [
            // 'assignablePadaliniai' => GetPadaliniaiForUpserts::execute('resources.create.all', $this->authorizer)
            'resources' => Resource::with('padalinys')->select('id', 'name', 'capacity', 'is_reservable', 'padalinys_id')->get()->map(function ($resource) use ($dateTimeRange) {
                $capacityAtDateTimeRange = $resource->getCapacityAtDateTimeRange($dateTimeRange['start'], $dateTimeRange['end']);

                return [
                    ...$resource->toArray(),
                    'capacityAtDateTimeRange' => $capacityAtDateTimeRange,
                    'lowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($capacityAtDateTimeRange),
                ];
            }),
            'dateTimeRange' => $dateTimeRange,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        $this->authorize('create', [Reservation::class, $this->authorizer]);

        $reservation = new Reservation();

        $reservation->fill($request->safe()->only(['name', 'description', 'start_time', 'end_time']));
        $reservation->save();

        $reservation->fresh();

        foreach ($request->validated('resources') as $resource) {
            $reservation->resources()->attach(
                $resource['id'], [
                    'quantity' => $resource['quantity'],
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'state' => 'created',
                ]
            );
        }

        $reservation->users()->attach(auth()->user()->id);

        return redirect()->route('reservations.show', $reservation->id)->with('success', 'Rezervacija sukurta.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        $this->authorize('view', [Reservation::class, $reservation, $this->authorizer]);

        $modelName = Str::of(class_basename($reservation))->camel()->plural();

        // TODO: this may need to be refactored to account for null
        $dateTimeRange = request()->input('dateTimeRange');

        return Inertia::render('Admin/Reservations/ShowReservation', [
            'reservation' => [
                // load pivot relationship comments
                ...$reservation->load('resources.pivot.comments', 'resources.padalinys', 'comments', 'activities.causer', 'users')->toArray(),
                'resources' => $reservation->resources->map(function ($resource) {
                    return [
                        ...$resource->toArray(),
                        'managers' => $resource->managers(),
                        'pivot' => $resource->pivot->append('approvable')->toArray(),
                    ];
                }),

            ],
            'allResources' => Inertia::lazy(fn () => Resource::with('padalinys')->select('id', 'name', 'is_reservable', 'capacity', 'padalinys_id')->get()->map(function ($resource) use ($dateTimeRange) {
                $capacityAtDateTimeRange = $resource->getCapacityAtDateTimeRange($dateTimeRange['start'], $dateTimeRange['end']);

                return [
                    ...$resource->toArray(),
                    'capacityAtDateTimeRange' => $capacityAtDateTimeRange,
                    'lowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($capacityAtDateTimeRange),
                ];
            })),
            'allUsers' => Inertia::lazy(fn () => User::select('id', 'name', 'profile_photo_path')->orderBy('name')->get()),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        $this->authorize('update', [Reservation::class, $reservation, $this->authorizer]);

        $dateTimeRange = request()->input('dateTimeRange') ?? [
            'start' => now()->setTimeFromTimeString('09:00')->addDay()->format('Uv'),
            'end' => now()->setTimeFromTimeString('17:00')->addDays(5)->format('Uv'),
        ];

        // dateTimeRange to numeric
        $dateTimeRange = [
            'start' => intval($dateTimeRange['start']),
            'end' => intval($dateTimeRange['end']),
        ];

        return Inertia::render('Admin/Reservations/EditReservation', [
            'reservation' => $reservation->mergeCasts([
                'start_time' => 'timestamp',
                'end_time' => 'timestamp',
            ])->toFullArray() + [
                'resources' => $reservation->resources->map(function ($resource) {
                    return [
                        ...$resource->toArray(),
                        'leftCapacity' => $resource->leftCapacity(),
                    ];
                }),
            ],
            'allResources' => Resource::select('id', 'name', 'capacity')->get()->map(function ($resource) use ($dateTimeRange) {
                $capacityAtDateTimeRange = $resource->getCapacityAtDateTimeRange($dateTimeRange['start'], $dateTimeRange['end']);

                return [
                    ...$resource->toArray(),
                    'capacityAtDateTimeRange' => $capacityAtDateTimeRange,
                    'lowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($capacityAtDateTimeRange),
                ];
            }),
            'dateTimeRange' => $dateTimeRange,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        //
    }

    public function addUsers(Reservation $reservation, Request $request)
    {
        $this->authorize('addUsers', [Reservation::class, $reservation, $this->authorizer]);

        $reservation->users()->syncWithoutDetaching($request->input('users'));

        return back()->with('success', 'Rezervacijos valdytojai pridėti.');
    }
}
