<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DuplicateCalendarAction;
use App\Actions\GetTenantsForUpserts;
use App\Actions\HandleModelMediaUploads;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexCalendarRequest;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Calendar;
use App\Models\Category;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CalendarController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexCalendarRequest $request)
    {
        $this->handleAuthorization('viewAny', Calendar::class);

        $query = Calendar::query()->with(['category', 'tenant:id,shortname']);

        $searchableColumns = ['title'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'calendars.read.padalinys',
            ]
        );

        $calendar = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        return $this->inertiaResponse('Admin/Calendar/IndexCalendarEvents', [
            'calendar' => [
                'data' => $calendar->getCollection()
                    ->map(function ($event) {
                        /** @var \App\Models\Calendar $event */
                        return $event->toFullArray();
                    }),
                'meta' => [
                    'total' => $calendar->total(),
                    'per_page' => $calendar->perPage(),
                    'current_page' => $calendar->currentPage(),
                    'last_page' => $calendar->lastPage(),
                    'from' => $calendar->firstItem(),
                    'to' => $calendar->lastItem(),
                ],
            ],
            'allCategories' => Category::all(['id', 'alias', 'name', 'description']),
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Calendar::class);

        return $this->inertiaResponse('Admin/Calendar/CreateCalendarEvent', [
            'assignableTenants' => GetTenantsForUpserts::execute('calendars.create.padalinys', $this->authorizer),
            'categories' => Category::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCalendarRequest $request)
    {
        $calendar = new Calendar;

        $calendar = $calendar->fill($request->except(['images', 'main_image']));
        $calendar->category_id = $request->input('category_id');

        $calendar->save();

        // Handle media uploads using centralized action
        HandleModelMediaUploads::execute($calendar, $request, [
            'main_image' => ['collection' => 'main_image', 'single' => true],
            'images' => ['collection' => 'images', 'single' => false],
        ]);

        return redirect()->route('calendar.index')->with('success', 'Kalendoriaus įvykis sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Calendar $calendar)
    {
        $this->handleAuthorization('view', $calendar);

        return $this->inertiaResponse('Admin/Calendar/ShowCalendarEvent', [
            'calendar' => $calendar,
            'images' => $calendar->getMedia('images'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Calendar $calendar)
    {
        $this->handleAuthorization('update', $calendar);

        return $this->inertiaResponse('Admin/Calendar/EditCalendarEvent', [
            'calendar' => [
                ...$calendar->toFullArray(),
                'images' => $calendar->getMedia('images')->map(
                    fn ($image) => [
                        'id' => $image->id,
                        'name' => $image->name,
                        'url' => $image->original_url,
                        'status' => 'finished',
                    ]
                ),
            ],
            'categories' => Category::all(),
            'assignableTenants' => GetTenantsForUpserts::execute('calendars.update.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCalendarRequest $request, Calendar $calendar)
    {
        DB::transaction(function () use ($request, $calendar) {
            // Exclude file fields from fill
            $calendar->fill($request->safe()->except(['images', 'main_image']));
            $calendar->category_id = $request->input('category_id');

            $calendar->save();

            // Handle media uploads using centralized action
            HandleModelMediaUploads::execute($calendar, $request, [
                'main_image' => ['collection' => 'main_image', 'single' => true],
                'images' => ['collection' => 'images', 'single' => false],
            ]);
        });

        return back()->with('success', 'Kalendoriaus įvykis sėkmingai atnaujintas!');
    }

    public function duplicate(Calendar $calendar)
    {
        $this->handleAuthorization('create', Calendar::class);

        $newCalendar = DuplicateCalendarAction::execute($calendar);

        return redirect()->route('calendar.edit', $newCalendar->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Calendar $calendar)
    {
        $this->handleAuthorization('delete', $calendar);

        $calendar->delete();

        return redirect()->route('calendar.index')->with('info', 'Kalendoriaus įvykis ištrintas!');
    }

    // TODO: something with this???
    public function destroyMedia(Calendar $calendar, Media $media)
    {
        $this->handleAuthorization('update', $calendar);

        $calendar->getMedia('images')->where('id', '=', $media->id)->first()?->delete();

        return back()->with('info', 'Nuotrauka ištrinta!');
    }
}
