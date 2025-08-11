<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreMeetingRequest;
use App\Models\Meeting;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use App\Services\ResourceServices\SharepointFileService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MeetingController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Meeting::class);

        $indexer = new ModelIndexer(new Meeting);

        $meetings = $indexer
            ->setEloquentQuery([fn (Builder $query) => $query->with(['institutions', 'agendaItems'])])
            ->filterAllColumns()
            ->sortAllColumns(['start_time' => 'descend'])
            ->builder->paginate(20);

        return $this->inertiaResponse('Admin/Representation/IndexMeeting', [
            'meetings' => $meetings,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMeetingRequest $request)
    {
        $validatedData = $request->safe();

        // generate title for meeting - YYYY-mm-dd HH.mm posėdis
        $title = Carbon::parse($validatedData['start_time'])->locale('lt-LT')->isoFormat('YYYY MMMM DD [d.] HH.mm [val.]').' posėdis';

        $meeting = Meeting::create([
            'start_time' => $validatedData['start_time'],
            'title' => $title,
        ]);

        $meeting->institutions()->attach($validatedData['institution_id']);

        $meeting->types()->attach($validatedData['type_id']);

        return back()->with(['success' => 'Posėdis sukurtas sėkmingai!', 'data' => $meeting]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting)
    {
        $this->handleAuthorization('view', $meeting);

        $meeting->load('institutions', 'activities.causer', 'files', 'comments', 'agendaItems', 'types')->load(['tasks' => function ($query) {
            $query->with('users', 'taskable');
        }]);

        // show meeting
        return $this->inertiaResponse('Admin/Representation/ShowMeeting', [
            'meeting' => [
                ...$meeting->toArray(),
                'sharepointPath' => $meeting->institutions->isNotEmpty() ? SharepointFileService::pathForFileableDriveItem($meeting) : null,
            ],
            'taskableInstitutions' => Inertia::lazy(fn () => $meeting->institutions->load('users')),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Meeting $meeting)
    {
        $this->handleAuthorization('update', $meeting);

        return $this->inertiaResponse('Admin/Representation/EditMeeting', [
            'meeting' => $meeting,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meeting $meeting)
    {
        $this->handleAuthorization('update', $meeting);

        $validated = $request->validate([
            // 'title' => 'required|string',
            'start_time' => 'required|date',
        ]);

        $validated['title'] = Carbon::parse($validated['start_time'])->locale('lt-LT')->isoFormat('YYYY MMMM DD [d.] HH.mm [val.]').' posėdis';

        $meeting->fill($validated);
        $meeting->save();

        $meeting->types()->sync([$request->type_id]);

        return back()->with('success', 'Posėdis atnaujintas sėkmingai!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting)
    {
        $this->handleAuthorization('delete', $meeting);

        $redirect_url = request()->redirect_to ?? back()->getTargetUrl();

        $meeting->delete();

        return redirect($redirect_url)->with('success', 'Posėdis ištrintas sėkmingai!');
    }

    public function restore(Meeting $meeting)
    {
        $this->handleAuthorization('restore', $meeting);

        $meeting->restore();

        return back()->with('success', 'Posėdis atkurtas!');
    }
}
