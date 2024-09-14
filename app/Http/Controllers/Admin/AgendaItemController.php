<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetInstitutionManagers;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgendaItemsRequest;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaItemController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

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
        $this->authorize('view', $agendaItem);

        return Inertia::render('Admin/Representation/ShowAgendaItem', [
            'agendaItem' => $agendaItem,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\AgendaItem  $agendaItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AgendaItem $agendaItem)
    {
        $this->authorize('update', $agendaItem);

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
        $this->authorize('delete', $agendaItem);

        $agendaItem->delete();

        return back()->with(['success' => 'Darbotvarkės punktas ištrintas sėkmingai!']);
    }
}
