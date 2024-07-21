<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Models\Calendar;
use App\Models\Category;
use App\Services\ModelIndexer;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CalendarController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Calendar::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Calendar(), request(), $this->authorizer);

        $calendar = $indexer
            ->setEloquentQuery([fn ($query) => $query->with('category')])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return Inertia::render('Admin/Calendar/IndexCalendarEvents', [
            'calendar' => $calendar,
            'allCategories' => Category::all(['id', 'alias', 'name', 'description']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Calendar::class, $this->authorizer]);

        return Inertia::render('Admin/Calendar/CreateCalendarEvent', [
            'assignableTenants' => GetTenantsForUpserts::execute('calendar.create.all', $this->authorizer),
            'categories' => Category::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCalendarRequest $request)
    {
        $calendar = new Calendar();

        $calendar = $calendar->fill($request->validated());

        $calendar->save();

        return redirect()->route('calendar.index')->with('success', 'Kalendoriaus įvykis sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Calendar $calendar)
    {
        $this->authorize('view', [Calendar::class, $calendar, $this->authorizer]);

        return Inertia::render('Admin/Calendar/ShowCalendarEvent', [
            'calendar' => $calendar,
            'images' => $calendar->getMedia('images'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Calendar $calendar)
    {
        $this->authorize('update', [Calendar::class, $calendar, $this->authorizer]);

        return Inertia::render('Admin/Calendar/EditCalendarEvent', [
            'calendar' => $calendar,
            'categories' => Category::all(),
            'images' => $calendar->getMedia('images'),
            'assignableTenants' => GetTenantsForUpserts::execute('calendar.update.all', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCalendarRequest $request, Calendar $calendar)
    {
        DB::transaction(function () use ($request, $calendar) {
            $calendar->fill($request->validated());

            $calendar->save();

            // if request has files

            $images = $request->file('images');

            if ($images) {
                foreach ($images as $image) {
                    $calendar->addMedia($image['file'])->toMediaCollection('images');
                }
            }

            $calendar->save();
        });

        return back()->with('success', 'Kalendoriaus įvykis sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Calendar $calendar)
    {
        $this->authorize('delete', [Calendar::class, $calendar, $this->authorizer]);

        $calendar->delete();

        return redirect()->route('calendar.index')->with('info', 'Kalendoriaus įvykis ištrintas!');
    }

    // TODO: something with this???
    public function destroyMedia(Calendar $calendar, Media $media)
    {
        $this->authorize('update', [Calendar::class, $calendar, $this->authorizer]);

        $calendar->getMedia('images')->where('id', '=', $media->id)->first()->delete();

        return back()->with('info', 'Nuotrauka ištrinta!');
    }
}
