<?php

namespace App\Http\Controllers\New;

use App\Models\SaziningaiObservers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class SaziningaiExamObserverController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $observers = SaziningaiObservers::all();

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
     * @param  \App\Models\SaziningaiObservers  $saziningaiObservers
     * @return \Illuminate\Http\Response
     */
    public function show(SaziningaiObservers $saziningaiObservers)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SaziningaiObservers  $saziningaiObservers
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiObservers $saziningaiObservers)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SaziningaiObservers  $saziningaiObservers
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SaziningaiObservers $saziningaiObservers)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SaziningaiObservers  $saziningaiObservers
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaziningaiObservers $saziningaiObservers)
    {
        //
    }
}
