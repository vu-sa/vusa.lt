<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetInstitutionManagers;
use App\Http\Controllers\ResourceController;
use App\Http\Requests\StoreAgendaItemsRequest;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaItemController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [AgendaItem::class, $this->authorizer]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [AgendaItem::class, $this->authorizer]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAgendaItemsRequest $request)
    {
        if ($request->has('agendaItemTitles')) {
            foreach ($request->safe()->agendaItemTitles as $agendaItemTitle) {
                AgendaItem::create([
                    'meeting_id' => $request->safe()->meeting_id,
                    'title' => $agendaItemTitle,
                ]);
            }

            if (isset($request->safe()->moreAgendaItemsUndefined)) {
                $meeting = Meeting::find($request->safe()->meeting_id);

                $institution = $meeting->institutions->first();

                $institutionManagers = GetInstitutionManagers::execute($institution);
                // get institution users and merge with institution managers
                $institutionUsers = $institution->users;
                $institutionAssociatedUsers = $institutionManagers->merge($institutionUsers);

                TaskService::storeTask('Sutvarkyti darbotvarkės klausimus', $meeting, $institutionAssociatedUsers);
            }
        }

        // pass event where there are agenda items that are not defined

        return back()->with(['success' => 'Darbotvarkės punktai sukurti sėkmingai!']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AgendaItem  $agendaItem
     * @return \Illuminate\Http\Response
     */
    public function show(AgendaItem $agendaItem)
    {
        $this->authorize('view', [AgendaItem::class, $agendaItem, $this->authorizer]);

        return Inertia::render('Admin/Representation/ShowAgendaItem', [
            'agendaItem' => $agendaItem,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AgendaItem  $agendaItem
     * @return \Illuminate\Http\Response
     */
    public function edit(AgendaItem $agendaItem)
    {
        $this->authorize('update', [AgendaItem::class, $agendaItem, $this->authorizer]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\AgendaItem  $agendaItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AgendaItem $agendaItem)
    {
        $this->authorize('update', [AgendaItem::class, $agendaItem, $this->authorizer]);

        $validated = $request->validate([
            'title' => 'required|string',
        ]);

        $agendaItem->fill($validated)->save();

        return back()->with(['success' => 'Darbotvarkės punktas atnaujintas sėkmingai!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AgendaItem  $agendaItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(AgendaItem $agendaItem)
    {
        $this->authorize('delete', [AgendaItem::class, $agendaItem, $this->authorizer]);

        $agendaItem->delete();

        return back()->with(['success' => 'Darbotvarkės punktas ištrintas sėkmingai!']);
    }
}
