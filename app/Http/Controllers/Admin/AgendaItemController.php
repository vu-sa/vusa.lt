<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pivots\AgendaItem;
use App\Http\Controllers\ResourceController;
use Illuminate\Http\Request;

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
    public function store(Request $request)
    {
        $this->authorize('create', [AgendaItem::class, $this->authorizer]);
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AgendaItem  $agendaItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AgendaItem $agendaItem)
    {
        $this->authorize('update', [AgendaItem::class, $agendaItem, $this->authorizer]);
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
    }
}