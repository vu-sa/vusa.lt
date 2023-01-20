<?php

namespace App\Http\Controllers\Admin;

use App\Models\Calendar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\User;
use App\Services\ModelIndexer;
use Illuminate\Support\Facades\Auth;

class CalendarController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Calendar::class, $this->authorizer]);
        
        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $calendar = $indexer->execute(Calendar::class, $search, 'title', $this->authorizer, null);

        return Inertia::render('Admin/Calendar/IndexCalendarEvents', [
            'calendar' => $calendar->paginate(20),
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
            'categories' => Category::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Calendar::class, $this->authorizer]);
        
        $request->validate([
            'date' => 'required|date',
            'title' => 'required',
            'description' => 'required',
        ]);

        $padalinys_id = User::find(Auth::id())->padalinys()?->id;

        if (is_null($padalinys_id)) {
            $padalinys_id = request()->user()->hasRole(config('permission.super_admin_role_name')) ? 16 : null;
        }

        Calendar::create([
            'date' => $request->date,
            'end_date' => $request->end_date,
            'title' => $request->title,
            'description' => $request->description,
            'padalinys_id' => $padalinys_id,
            'location' => $request->location,
            'url' => $request->url,
            'category' => $request->category,
            'extra_attributes' => $request->extra_attributes
        ]);

        return redirect()->route('calendar.index')->with('success', 'Kalendoriaus įvykis sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function show(Calendar $calendar)
    {
        $this->authorize('view', [Calendar::class, $calendar, $this->authorizer]);
        
        return Inertia::render('Admin/Calendar/ShowCalendarEvent', [
            'calendar' => $calendar,
            'images' => $calendar->getMedia('images')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function edit(Calendar $calendar)
    {
        $this->authorize('update', [Calendar::class, $calendar, $this->authorizer]);
        
        return Inertia::render('Admin/Calendar/EditCalendarEvent', [
            'calendar' => $calendar,
            'categories' => Category::all(),
            'images' => $calendar->getMedia('images')
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Calendar $calendar)
    {
        $this->authorize('update', [Calendar::class, $calendar, $this->authorizer]);

        $request->validate([
            'date' => 'required|date',
            'title' => 'required',
            'description' => 'required',
        ]);

        DB::transaction(function () use ($request, $calendar) {
            $calendar->update($request->only(['date', 'end_date', 'title', 'description', 'location', 'url', 'category', 'extra_attributes']));

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
     * @param  \App\Models\Calendar $calendar
     * @return \Illuminate\Http\Response
     */
    public function destroy(Calendar $calendar)
    {
        $this->authorize('delete', [Calendar::class, $calendar, $this->authorizer]);
        
        $calendar->delete();

        return redirect()->route('calendar.index')->with('info', 'Kalendoriaus įvykis ištrintas!');
    }
    // TODO: something with this???
    public function destroyMedia(Calendar $calendar, Media $media) {
        
        $this->authorize('destroyMedia', $calendar);
        
        $calendar->getMedia('images')->where('id', '=', $media->id)->first()->delete();

        return back()->with('info', 'Nuotrauka ištrinta!');
    }
}
