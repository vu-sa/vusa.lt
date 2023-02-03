<?php

namespace App\Http\Controllers\Admin;

use App\Models\SaziningaiExamObserver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;

class SaziningaiExamObserversController extends ResourceController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [SaziningaiExamObserver::class, $this->authorizer]);
        // $observers = SaziningaiExamObserver::all();

        // return Inertia::render('Admin/Saziningai/Observers/Index', [
        //     'observers' => $observers,
        // ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [SaziningaiExamObserver::class, $this->authorizer]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [SaziningaiExamObserver::class, $this->authorizer]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Http\Response
     */
    public function show(SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('view', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('update', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('update', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('delete', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }
}
