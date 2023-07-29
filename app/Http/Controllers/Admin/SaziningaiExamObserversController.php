<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\SaziningaiExamObserver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaziningaiExamObserversController extends LaravelResourceController
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [SaziningaiExamObserver::class, $this->authorizer]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('view', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('update', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('update', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaziningaiExamObserver $saziningaiExamObserver)
    {
        $this->authorize('delete', [SaziningaiExamObserver::class, $saziningaiExamObserver, $this->authorizer]);
    }
}
