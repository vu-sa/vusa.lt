<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreMeetingRequest;
use App\Models\Meeting as Meeting;
use App\Services\ModelIndexer;
use App\Services\ResourceServices\SharepointFileService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MeetingController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Meeting::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Meeting(), request(), $this->authorizer);

        $meetings = $indexer
            ->setEloquentQuery([fn (Builder $query) => $query->with(['institutions', 'agendaItems'])])
            ->filterAllColumns()
            ->sortAllColumns(['start_time' => 'descend'])
            ->builder->paginate(20);

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
        // generate title for meeting - YYYY-mm-dd HH.mm posėdis
        $title = Carbon::parse($request->safe()->only('start_time')['start_time'])->locale('lt-LT')->isoFormat('YYYY MMMM DD [d.] HH.mm [val.]').' posėdis';

        $meeting = Meeting::create($request->safe()->only('start_time') + ['title' => $title]);

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

        $meeting->load('institutions', 'activities.causer', 'files', 'comments', 'agendaItems')->load(['tasks' => function ($query) {
            $query->with('users', 'taskable');
        }]);

        // show meeting
        return Inertia::render('Admin/Representation/ShowMeeting', [
            'meeting' => [
                ...$meeting->toArray(),
                'sharepointPath' => $meeting->institutions ? SharepointFileService::pathForFileableDriveItem($meeting) : null,
            ],
            'taskableInstitutions' => Inertia::lazy(fn () => $meeting->institutions->load('users')),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('update', [Meeting::class, $meeting, $this->authorizer]);

        $validated = $request->validate([
            // 'title' => 'required|string',
            'start_time' => 'required|integer',
        ]);

        $validated['start_time'] = Carbon::createFromTimestamp($validated['start_time'] / 1000)->toDateTime();
        $validated['title'] = Carbon::parse($validated['start_time'])->locale('lt-LT')->isoFormat('YYYY MMMM DD [d.] HH.mm [val.]').' posėdis';

        $meeting->fill($validated);
        $meeting->save();

        return back()->with('success', 'Posėdis atnaujintas sėkmingai!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', [Meeting::class, $meeting, $this->authorizer]);

        // delete meeting
        $meeting->delete();

        return back()->with('success', 'Posėdis ištrintas sėkmingai!');
    }

    public function restore(Meeting $meeting)
    {
        $this->authorize('restore', [Meeting::class, $meeting, $this->authorizer]);

        $meeting->restore();

        return back()->with('success', 'Posėdis atkurtas!');
    }
}
