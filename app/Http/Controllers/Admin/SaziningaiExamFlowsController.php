<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\SaziningaiExamFlow;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaziningaiExamFlowsController extends LaravelResourceController
{

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [SaziningaiExamFlow::class, $this->authorizer]);

        // Store new flow
        $saziningaiExamFlow = new SaziningaiExamFlow();
        $saziningaiExamFlow->exam_uuid = $request->exam_uuid;
        $saziningaiExamFlow->start_time = date('Y-m-d H:i:s', $request->start_time);
        $saziningaiExamFlow->save();

        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
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
}
