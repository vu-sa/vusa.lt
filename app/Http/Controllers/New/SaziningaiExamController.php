<?php

namespace App\Http\Controllers\New;

use App\Http\Controllers\Controller as Controller;
use App\Models\Padalinys;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExam;
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

        $exams = SaziningaiExam::all();

        // dd($exams->unique('padalinys_id')->toArray());

        return Inertia::render('Admin/Saziningai/Exams/Index', [
            'exams' => $exams->map(function ($exam) {

                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'subject_name' => $exam->subject_name,
                    'exam_holders' => $exam->exam_holders,
                    'padalinys' => $exam->padalinys->shortname_vu,
                    'created_at' => $exam->created_at->format('Y-m-d H:i'),
                    'flow_date' => date_create($exam->flows->first()->start_time)->format('Y-m-d H:i'),
                    'flow_count' => $exam->flows->count(),
                    'observer_count' => $exam->observers->count(),
                ];
            }),
            'padaliniai' => $exams->unique('padalinys_id')->map(function ($exam) {
                return $exam->padalinys->shortname_vu;
            }),
            'create_url' => route('saziningaiExams.create'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Saziningai/Exams/Create', [
            'padaliniai' => Padalinys::orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'shortname_vu' => $padalinys->shortname_vu,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'max:50'],
            'last_name' => ['required', 'max:50'],
            'email' => ['required', 'max:50', 'email'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Http\Response
     */
    public function show(SaziningaiExam $saziningaiExam)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Saziningai  $saziningai
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiExam $saziningaiExam)
    {

        return Inertia::render('Admin/Saziningai/Exams/Edit', [
            'exam' => [
                'id' => $saziningaiExam->id,
                'name' => $saziningaiExam->name,
                'phone' => $saziningaiExam->phone,
                'email' => $saziningaiExam->email,
                'exam_type' => $saziningaiExam->exam_type,
                'padalinys_id' => $saziningaiExam->padalinys_id,
                'place' => $saziningaiExam->place,
                'duration' => $saziningaiExam->duration,
                'subject_name' => $saziningaiExam->subject_name,
                'exam_holders' => $saziningaiExam->exam_holders,
                'students_need' => $saziningaiExam->students_need,
                'created_at' => $saziningaiExam->created_at->format('Y-m-d H:i'),
                'updated_at' => $saziningaiExam->created_at->format('Y-m-d H:i'),
            ],
            'padaliniai' => Padalinys::orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'shortname_vu' => $padalinys->shortname_vu,
                ];
            }),
            'flows' => $saziningaiExam->flows,
            'observers' => $saziningaiExam->observers
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SaziningaiExam $saziningaiExam)
    {
        $saziningaiExam->update($request->only('name', 'phone', 'email', 'exam_type', 'padalinys_id', 'place', 'duration', 'subject_name', 'exam_holders', 'students_need'));
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaziningaiExam $saziningaiExam)
    {
        $saziningaiExam->delete();

        return redirect()->route('saziningaiExams.index');
    }
}
