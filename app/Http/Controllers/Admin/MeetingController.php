<?php

namespace App\Http\Controllers\Admin;

use App\Models\Meeting as Meeting;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ResourceController;
use App\Http\Requests\StoreMeetingRequest;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\MeetingService as MeetingService;
use App\Services\SharepointAppGraph;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Carbon;

class MeetingController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Meeting::class, $this->authorizer]);

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
    // public function create()
    // {
    //     $this->authorize('create', [Meeting::class, $this->authorizer]);

    //     return Inertia::render('Admin/Representation/CreateMeeting');
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMeetingRequest $request)
    {
        $meeting = Meeting::create($request->safe()->only('start_time'));

        $meeting->institutions()->attach($request->safe()->institution_id);

        return back()->with(['success' => 'Posėdis sukurtas sėkmingai!', 'data' => $meeting]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Meeting  $meting
     * @return \Illuminate\Http\Response
     */
    public function show(Meeting $meeting)
    { 
        $this->authorize('view', [Meeting::class, $meeting, $this->authorizer]);
        
        $sharepointFiles = [];
        
        if ($meeting->documents()->count() > 0) {
            $graph = new SharepointAppGraph();
        
            $sharepointFiles = $graph->collectSharepointFiles($meeting->documents);
        }
        
        $meeting->load('institutions', 'tasks', 'activities.causer', 'comments', 'agendaItems', 'tasks.taskable', 'tasks.users');

        // show meeting
        return Inertia::render('Admin/Representation/ShowMeeting', [
            'meeting' => $meeting,
            'sharepointFiles' => $sharepointFiles,
            'taskableInstitutions' => Inertia::lazy(fn () => $meeting->institutions->load('users')),
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
        $this->authorize('update', [Meeting::class, $meeting, $this->authorizer]);

        return Inertia::render('Admin/Representation/EditMeeting', [
            'meeting' => $meeting,
        ]);
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
        $this->authorize('update', [Meeting::class, $meeting, $this->authorizer]);
        
        $validated = $request->validate([
            'start_time' => 'required|integer',
        ]);

        $validated['start_time'] = Carbon::createFromTimestamp($validated['start_time'] / 1000)->toDateTime();

        $meeting->update($validated);

        return back()->with('success', 'Posėdis atnaujintas sėkmingai!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\Response
     */
    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', [Meeting::class, $meeting, $this->authorizer]);
        
        // delete meeting
        $meeting->delete();

        return back()->with('success', 'Posėdis ištrintas sėkmingai!');
    }
}
