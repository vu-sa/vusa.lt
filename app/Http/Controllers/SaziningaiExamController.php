<?php

namespace App\Http\Controllers;

use App\Models\Saziningai;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaziningaiExamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Admin/Saziningai/Exams/Index', [
            'exams' => Saziningai::all()
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
     * @param  \App\Models\Saziningai  $saziningai
     * @return \Illuminate\Http\Response
     */
    public function show(Saziningai $saziningai)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Saziningai  $saziningai
     * @return \Illuminate\Http\Response
     */
    public function edit(Saziningai $saziningai)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Saziningai  $saziningai
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Saziningai $saziningai)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Http\Response
     */
    public function destroy(Saziningai $saziningaiExam)
    {
        //
    }
}
