<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreAgendaItemsRequest;
use App\Http\Requests\UpdateAgendaItemRequest;
use App\Models\Pivots\AgendaItem;
use App\Services\ModelAuthorizer as Authorizer;

class AgendaItemController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAgendaItemsRequest $request)
    {
        if ($request->has('agendaItemTitles')) {
            $validatedData = $request->safe();
            foreach ($validatedData['agendaItemTitles'] as $agendaItemTitle) {
                AgendaItem::create([
                    'meeting_id' => $validatedData['meeting_id'],
                    'title' => $agendaItemTitle,
                ]);
            }

            // We no longer create tasks for placeholder agenda items
        }

        return back()->with(['success' => 'Darbotvarkės punktai sukurti sėkmingai!']);
    }

    /**
     * Display the specified resource.
     */
    public function show(AgendaItem $agendaItem)
    {
        $this->handleAuthorization('view', $agendaItem);

        return $this->inertiaResponse('Admin/Representation/ShowAgendaItem', [
            'agendaItem' => $agendaItem,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgendaItemRequest $request, AgendaItem $agendaItem)
    {
        $agendaItem->fill($request->validated());
        $agendaItem->save();

        return back()->with('success', 'Darbotvarkės punktas atnaujintas sėkmingai!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgendaItem $agendaItem)
    {
        $this->handleAuthorization('delete', $agendaItem);

        $agendaItem->delete();

        return back()->with(['success' => 'Darbotvarkės punktas ištrintas sėkmingai!']);
    }
}
