<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DuplicateCalendarAction;
use App\Actions\GetTenantsForUpserts;
use App\Actions\HandleModelMediaUploads;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Models\Calendar;
use App\Models\Category;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CalendarController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Calendar::class);

        $indexer = new ModelIndexer(new Calendar);

        $calendar = $indexer
            ->setEloquentQuery([fn ($query) => $query->with('category')])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return $this->inertiaResponse('Admin/Calendar/IndexCalendarEvents', [
            'calendar' => $calendar,
            'allCategories' => Category::all(['id', 'alias', 'name', 'description']),
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
