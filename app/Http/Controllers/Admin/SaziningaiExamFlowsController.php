<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaziningaiExamFlow;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaziningaiExamFlowsController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SaziningaiExamFlow::class, 'saziningaiExamFlow');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Institution::class, $this->authorizer]);
        //
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
        // Store new flow
        $saziningaiExamFlow = new SaziningaiExamFlow();
        $saziningaiExamFlow->exam_uuid = $request->exam_uuid;
        $saziningaiExamFlow->start_time =  date('Y-m-d H:i:s', $request->start_time);
        $saziningaiExamFlow->save();

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Http\Response
     */
    public function show(SaziningaiExamFlow $saziningaiExamFlow)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiExamFlow $saziningaiExamFlow)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SaziningaiExamFlow $saziningaiExamFlow)
    {
        // Update the flow time
        $saziningaiExamFlow->start_time = date('Y-m-d H:i:s', $request->start_time);
        $saziningaiExamFlow->save();

        // Redirect to the exam
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaziningaiExamFlow $saziningaiExamFlow)
    {
        //
    }
}
