<?php

namespace App\Http\Controllers\New;

use App\Models\SaziningaiExamObserver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class SaziningaiExamObserversController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $observers = SaziningaiExamObserver::all();

        return Inertia::render('Admin/Saziningai/Observers/Index', [
            'observers' => $observers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Http\Response
     */
    public function show(SaziningaiExamObserver $saziningaiExamObserver)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiExamObserver $saziningaiExamObserver)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaziningaiExamObserver $saziningaiExamObserver)
    {
        //
    }
}
