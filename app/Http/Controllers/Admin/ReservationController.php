<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApplyReservationIndexFilters;
use App\Actions\EnsureReservationCapacity;
use App\Actions\NotifyAffectedReservationDrafts;
use App\Actions\SerializeReservationCart;
use App\Actions\SerializeReservationsForTable;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\AssignedToResourceNotification;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
/* use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests; */
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReservationController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService)
    {
        /* $this->middleware([HandlePrecognitiveRequests::class])->only(['store', 'update']); */
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexReservationRequest $request)
    {
        $this->handleAuthorization('viewList', Reservation::class);

        $query = Reservation::query()->with(SerializeReservationsForTable::EAGER_LOADS);

        $query = ApplyReservationIndexFilters::execute($query, $request, $request->user(), $this->authorizer);

        $searchableColumns = ['name', 'description'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
        );

        $deletedCount = $this->getTrashedCount($query);

        $reservations = $query->paginate($request->getPerPage())
            ->withQueryString();

        $allowedTenantIds = $this->authorizer->tenants($request->user(), 'reservations.read.padalinys')->pluck('id');
        $managesResources = $this->authorizer
            ->tenants($request->user(), config('permission.resource_managership_indicating_permission'))
            ->isNotEmpty();

        return $this->inertiaResponse('Admin/Reservations/IndexReservation', [
            'reservations' => [
                'data' => SerializeReservationsForTable::execute($reservations->getCollection(), $request->user(), $this->authorizer),
                'meta' => [
                    'total' => $reservations->total(),
                    'per_page' => $reservations->perPage(),
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'from' => $reservations->firstItem(),
                    'to' => $reservations->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
            'managesResources' => $managesResources,
            'reservationCart' => SerializeReservationCart::execute($request->user()),
            'onlyOwn' => ! $request->user()->can('viewAny', Reservation::class),
            'activeReservations' => Reservation::whereHas('resources', function ($query) use ($allowedTenantIds): void {
                $query->whereIn('resources.tenant_id', $allowedTenantIds);
            })->with(['resources.tenant', 'users'])->get(),
        ]);
    }

    /**
     * The checkout: the user's reservation cart with its name, description and period.
     */
    public function create()
    {
        $this->handleAuthorization('create', [Reservation::class, $this->authorizer]);

        return $this->inertiaResponse('Admin/Reservations/CreateReservation', [
            'reservationCart' => SerializeReservationCart::execute(request()->user()),
            'defaultDateTimeRange' => [
                'start' => (int) now()->setTimeFromTimeString('09:00')->addDay()->format('Uv'),
                'end' => (int) now()->setTimeFromTimeString('17:00')->addDays(5)->format('Uv'),
            ],
        ]);
    }

    /**
     * Submitting the cart: capacity is checked under a row lock, and the draft is consumed with
     * the reservation so a failed check leaves it intact.
     */
    public function store(StoreReservationRequest $request)
    {
        $this->handleAuthorization('create', [Reservation::class, $this->authorizer]);

        $reservation = DB::transaction(function () use ($request): Reservation {
            $reservation = new Reservation;

            $reservation->fill($request->safe()->only(['name', 'description', 'start_time', 'end_time']));

            EnsureReservationCapacity::execute($request->validated('resources'), $reservation->start_time, $reservation->end_time);

            $reservation->save();

            foreach ($request->validated('resources') as $resource) {
                $reservation->attachAudited(
                    'resources',
                    $resource['id'], [
                        'quantity' => $resource['quantity'],
                        'start_time' => $reservation->start_time,
                        'end_time' => $reservation->end_time,
                        'state' => 'created',
                    ]
                );
            }

            $reservation->attachAudited('users', auth()->id());

            $request->user()->reservationDraft()->delete();

            return $reservation;
        });

        NotifyAffectedReservationDrafts::execute($reservation);

        return redirect()->route('reservations.show', $reservation->id)->with('success', $this->entityMessage('created', 'reservation'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        $this->handleAuthorization('view', [Reservation::class, $reservation, $this->authorizer]);

        $modelName = Str::of(class_basename($reservation))->camel()->plural();

        // TODO: this may need to be refactored to account for null
        $dateTimeRange = request()->input('dateTimeRange');
        $exceptReservations = collect(request()->input('except-reservations'))->unique()->toArray();
        $exceptResources = collect(request()->input('except-resources'))->unique()->toArray();

        $user = request()->user();

        $reservation->load('users', 'resources.media', 'resources.pivot.approvals.user', 'resources.pivot.approvals.revertedBy', 'resources.tenant');

        return $this->inertiaResponse('Admin/Reservations/ShowReservation', [
            'reservation' => [
                ...$reservation->toArray(),
                'resources' => $reservation->resources->map(function ($resource) use ($reservation, $user) {

                    // This is used to update the left capacity of resources already attached to the reservation
                    $capacityAtDateTimeRange = $resource->getCapacityAtDateTimeRange($reservation->start_time, $reservation->end_time);

                    return [
                        ...$resource->toArray(),
                        'managers' => $resource->managers(),
                        'pivot' => $resource->pivot->append('approvable')->toArray(),
                        'lowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($capacityAtDateTimeRange),
                        // Per resource: `resources.update.padalinys` is tenant-scoped, so one flag would be wrong.
                        'can_edit' => $user->can('update', $resource),
                    ];
                }),
            ],
            // The same payload the collection page reads, so a row's approve / reject / backtrack /
            // cancel flags cannot disagree with the list the user came from.
            'decisionTarget' => SerializeReservationsForTable::execute([$reservation], $user, $this->authorizer)[0],
            'can' => [
                'update' => $user->can('update', $reservation),
                'delete' => $user->can('delete', $reservation),
            ],
            'allResources' => Inertia::optional(fn () => Resource::query()->with('tenant')->select(['id', 'name', 'is_reservable', 'capacity', 'tenant_id'])->get()->map(function ($resource) use ($dateTimeRange, $exceptResources, $exceptReservations) {

                $capacityAtDateTimeRange = $resource->getCapacityAtDateTimeRange($dateTimeRange['start'], $dateTimeRange['end'], $exceptReservations, $exceptResources);

                return [
                    ...$resource->toArray(),
                    'tenant' => [
                        'id' => $resource->tenant->id,
                        'shortname' => __($resource->tenant->shortname),
                    ],
                    'capacityAtDateTimeRange' => $capacityAtDateTimeRange,
                    'lowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($capacityAtDateTimeRange),
                ];
            })),
            'allUsers' => Inertia::optional(fn () => User::select('id', 'name', 'profile_photo_path')->orderBy('name')->get()),
        ]);
    }

    /**
     * A reservation is never edited as a whole. Its name and description are set
     * at creation, and everything else — resources, times, quantities — is changed
     * per resource through {@see ReservationResourceController::update()}. The
     * `edit` and `update` routes are therefore not registered.
     */

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        $this->handleAuthorization('delete', [Reservation::class, $reservation, $this->authorizer]);

        $reservation->delete();

        return back()->with('success', $this->entityMessage('deleted', 'reservation'));
    }

    public function restore(Reservation $reservation): RedirectResponse
    {
        return $this->restoreModel($reservation, $this->entityMessage('restored', 'reservation'));
    }

    public function addUsers(Reservation $reservation, Request $request)
    {
        $this->handleAuthorization('addUsers', [Reservation::class, $reservation, $this->authorizer]);

        $old_users = $reservation->users;

        $reservation->auditRelationChange('users', fn () => $reservation->users()->syncWithoutDetaching($request->input('users')));

        Notification::send($reservation->refresh()->users->diff($old_users), AssignedToResourceNotification::fromModel($reservation, auth()->user()));

        return back()->with('success', __('messages.users_attached_to_reservation'));
    }

    public function forceDelete(Reservation $reservation): RedirectResponse
    {
        return $this->forceDeleteModel($reservation);
    }
}
