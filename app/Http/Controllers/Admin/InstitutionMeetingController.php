<?php

namespace App\Http\Controllers\Admin;

use App\Models\InstitutionMeeting as Meeting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\InstitutionMeetingService as MeetingService;
use App\Services\SharepointAppGraph;
use Illuminate\Support\Benchmark;

class InstitutionMeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::with('institutions')->paginate(20);

        return Inertia::render('Admin/Representation/IndexMeeting', [
            'meetings' => $meetings,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|integer',
        ]);

        // convert timestamp to date
        $validated['start_time'] = date('Y-m-d H:i:s', $validated['start_time'] / 1000);

        $meeting = Meeting::create($validated);

        if ($request->has('mattersForm')) {
            MeetingService::storeAndAttachMattersToMeeting($request->mattersForm, $meeting);
        }

        return back()->with('success', 'Posėdis sukurtas sėkmingai!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Meeting  $meting
     * @return \Illuminate\Http\Response
     */
    public function show(Meeting $meeting)
    { 
        $sharepointFiles = [];
        
        if ($meeting->documents()->count() > 0) {
            $graph = new SharepointAppGraph();
        
            $sharepointFiles = $graph->collectSharepointFiles($meeting->documents);
        }
        
        $meeting->load('matters.institutions', 'tasks', 'activities.causer');

        // show meeting
        return Inertia::render('Admin/Representation/ShowMeeting', [
            'meeting' => $meeting,
            'sharepointFiles' => $sharepointFiles,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\Response
     */
    public function edit(Meeting $meeting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Meeting $meeting)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\Response
     */
    public function destroy(Meeting $meeting)
    {
        // delete meeting
        $meeting->delete();

        return back()->with('success', 'Posėdis ištrintas sėkmingai!');
    }
}
