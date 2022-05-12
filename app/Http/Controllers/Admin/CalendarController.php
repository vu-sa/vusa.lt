<?php

namespace App\Http\Controllers\Admin;

use App\Models\Calendar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Calendar::class, 'calendar');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $calendar = Calendar::orderByDesc('date')->paginate(20);

        return Inertia::render('Admin/Calendar/Events/Index', [
            'calendar' => $calendar,
            'create_url' => route('calendar.create'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Calendar/Events/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'title' => 'required',
            'description' => 'required',
        ]);

        Calendar::create([
            'date' => $request->date,
            'title' => $request->title,
            'description' => $request->description,
            'url' => $request->url,
            'category' => $request->category,
        ]);

        return redirect()->route('calendar.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function show(Calendar $calendar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function edit(Calendar $calendar)
    {
        return Inertia::render('Admin/Calendar/Events/Edit', [
            'calendar' => $calendar->toArray(),
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
        $calendar->update($request->only('title', 'date', 'description', 'category', 'url'));

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Calendar $calendar
     * @return \Illuminate\Http\Response
     */
    public function destroy(Calendar $calendar)
    {
        $calendar->delete();

        return redirect()->route('calendar.index');
    }
}
