<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgendaItemsRequest;
use App\Http\Requests\UpdateAgendaItemRequest;
use App\Models\Pivots\AgendaItem;
use App\Services\ModelAuthorizer as Authorizer;
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

            // We no longer create tasks for placeholder agenda items
        }

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
    public function update(UpdateAgendaItemRequest $request, AgendaItem $agendaItem)
    {
        $agendaItem->fill($request->validated());
        $agendaItem->save();

        return back()->with('success', 'Darbotvarkės punktas atnaujintas sėkmingai!');
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
