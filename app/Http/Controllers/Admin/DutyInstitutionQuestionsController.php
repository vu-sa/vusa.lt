<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\DoingType;
use App\Models\DutyInstitution;
use App\Models\Question;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DutyInstitutionQuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function index(DutyInstitution $dutyInstitution)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function create(DutyInstitution $dutyInstitution)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, DutyInstitution $dutyInstitution)
    {
        $validated = $request->validate([
            'title' => 'required',
            'description' => 'string',
        ]);

        // add status "Sukurtas" to validated
        $validated['status'] = 'Sukurtas';
        $validated['institution_id'] = $dutyInstitution->id;
        // TODO: need to set question group logically
        $validated['question_group_id'] = 1;

        $question = Question::create($validated);

        return redirect()->route('dutyInstitutions.questions.show', [$dutyInstitution, $question])->with('success', 'Klausimas sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function show(DutyInstitution $dutyInstitution, Question $question)
    {
        $question = $question->load(['doings' => function ($query) {
            $query->orderBy('date');
        }])->load('institution', 'activities');
        
        return Inertia::render('Admin/Questions/ShowQuestion', [
            'question' => [
                ...$question->toArray(),
                'activities' => $question->activities->map(function ($activity) {
                    return [
                        ...$activity->toArray(),
                        'causer' => $activity->causer,
                    ];
                }),
            ],
            'doingTypes' => DoingType::all()->map(function ($doingType) {
                return [
                    'value' => $doingType->id,
                    'label' => $doingType->title,
                ];
            }),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function edit(DutyInstitution $dutyInstitution, Question $question)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DutyInstitution $dutyInstitution, Question $question)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function destroy(DutyInstitution $dutyInstitution, Question $question)
    {
        //
    }
}
