<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Padalinys;
use App\Models\SaziningaiExam;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaziningaiExamsController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [SaziningaiExam::class, $this->authorizer]);

        $exams = SaziningaiExam::with(['flows' => function ($query) {
            $query->select('id', 'exam_uuid', 'start_time')->orderBy('start_time', 'asc');
        }])->with(['padalinys'])->get();

        return Inertia::render('Admin/Saziningai/IndexSaziningaiExams', [
            'exams' => $exams->paginate(20),
            'padaliniai' => $exams->unique('padalinys_id')->map(function ($exam) {
                return $exam->padalinys?->shortname_vu;
            }),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [SaziningaiExam::class, $this->authorizer]);

        return redirect()->route('saziningaiExamRegistration');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [SaziningaiExam::class, $this->authorizer]);

        $request->validate([
            'email' => ['required', 'email'],
            'subject_name' => ['required'],
            'padalinys_id' => ['required'],
        ]);

        SaziningaiExam::create([
            'uuid' => bin2hex(random_bytes(15)),
            'subject_name' => $request->subject_name,
            'padalinys_id' => $request->padalinys_id,
            'email' => $request->email,
            'duration' => $request->duration,
            'exam_holders' => $request->exam_holders,
            'exam_type' => $request->exam_type,
            'phone' => $request->phone,
            'students_need' => $request->students_need,
        ]);

        return redirect()->route('saziningaiExams.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Saziningai  $saziningai
     * @return \Illuminate\Http\Response
     */
    public function edit(SaziningaiExam $saziningaiExam)
    {
        $this->authorize('update', [SaziningaiExam::class, $saziningaiExam, $this->authorizer]);

        return Inertia::render('Admin/Saziningai/EditSaziningaiExam', [
            'exam' => [
                'id' => $saziningaiExam->id,
                'uuid' => $saziningaiExam->uuid,
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
            'flows' => $saziningaiExam->flows->sortBy('start_time')->map(function ($flow) {
                return [
                    'id' => $flow->id,
                    'start_time' => $flow->start_time,
                    'end_time' => $flow->end_time,
                    'observers' => $flow?->observers->map(function ($observer) {
                        return [
                            'id' => $observer->id,
                            'name' => $observer->name,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SaziningaiExam $saziningaiExam)
    {
        $this->authorize('update', [SaziningaiExam::class, $saziningaiExam, $this->authorizer]);

        $request->validate([
            'email' => ['required', 'email'],
            'subject_name' => ['required'],
            'padalinys_id' => ['required'],
        ]);

        $saziningaiExam->update($request->only('name', 'phone', 'email', 'exam_type', 'padalinys_id', 'place', 'duration', 'subject_name', 'exam_holders', 'students_need'));

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaziningaiExam $saziningaiExam)
    {
        $this->authorize('delete', [SaziningaiExam::class, $saziningaiExam, $this->authorizer]);

        $saziningaiExam->delete();

        return redirect()->route('saziningaiExams.index');
    }
}
