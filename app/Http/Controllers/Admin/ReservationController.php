<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Resource;
use App\Services\ModelIndexer;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
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

        $reservations = ModelIndexer::filterByAuthorized($reservations, $this->authorizer);

        return Inertia::render('Admin/Reservations/IndexReservation', [
            'reservations' => $reservations->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Reservation::class, $this->authorizer]);

        return Inertia::render('Admin/Reservations/CreateReservation', [
            // 'assignablePadaliniai' => GetPadaliniaiForUpserts::execute('resources.create.all', $this->authorizer)
            'resources' => Resource::select('id', 'name', 'capacity')->get()->map(function ($resource) {
                return [
                    ...$resource->toArray(),
                    'leftCapacity' => $resource->leftCapacity(),
                ];
            })
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

        foreach ($request->validated('resources') as $resource) {
            $reservation->resources()->attach(
                $resource['id'], [
                    'quantity' => $resource['quantity'],
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'state' => 'created'
                ]
            );
        }

        return redirect()->route('reservations.index')->with('success', 'Rezervacija sukurta.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        $this->authorize('update', [Resource::class, $this->authorizer]);

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
                })
            ],
            'allResources' => Resource::select('id', 'name', 'capacity')->get()->map(function ($resource) {
                return [
                    ...$resource->toArray(),
                    'leftCapacity' => $resource->leftCapacity(),
                ];
            })
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
}
