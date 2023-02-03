<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResourceController;
use App\Models\SaziningaiExamFlow;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaziningaiExamFlowsController extends ResourceController
{
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [SaziningaiExamFlow::class, $this->authorizer]);
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [SaziningaiExamFlow::class, $this->authorizer]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [SaziningaiExamFlow::class, $this->authorizer]);
        
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
        $this->authorize('view', [SaziningaiExamFlow::class, $saziningaiExamFlow, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiExamFlow $saziningaiExamFlow)
    {
        $this->authorize('update', [SaziningaiExamFlow::class, $saziningaiExamFlow, $this->authorizer]);

        return Inertia::render('Admin/SaziningaiExamFlows/Edit', [
            'saziningaiExamFlow' => $saziningaiExamFlow,
        ]);
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
        $this->authorize('update', [SaziningaiExamFlow::class, $saziningaiExamFlow, $this->authorizer]);
        
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
        $this->authorize('delete', [SaziningaiExamFlow::class, $saziningaiExamFlow, $this->authorizer]);
    }
}
