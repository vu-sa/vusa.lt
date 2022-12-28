<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Type;
use App\Models\Doing;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Question::class, 'question');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $search = request()->input('search');

        $questions = Question::when(!request()->user()->hasRole('Super Admin'), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%");
        })->paginate(20);

        return Inertia::render('Admin/Questions/IndexQuestions', [
            'questions' => $questions,
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
        $validated = $request->validate([
            'title' => 'required',
        ]);

        // add status "Sukurtas" to validated
        $validated['status'] = 'Sukurtas';
        $validated['institution_id'] = $request->duty_institution_id;
        $validated['question_group_id'] = $request->question_group_id;
        // if no question group, create one
        // if (is_null($request->question_group_id)) {
        //     $questionGroup = QuestionGroup::create([
        //         'title' => 'Klausimo \"' . $validated['title'] . '\" grupė',
        //     ]);
        //     $validated['question_group_id'] = $questionGroup->id;
        // } else {
        //     // ...
        // }

        $question = Question::create($validated);

        // reminder to adjust question creation in the frontend
        return redirect()->back()->with('data', $question)->with('success', 'Klausimas sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function show(Question $question)
    {
        $question = $question->load(['doings' => function ($query) {
            $query->orderBy('date');
        }])->load('institution', 'activities', 'doings.comments', 'doings.tasks', 'doings.documents');
        
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
            'doingTypes' => Type::where('model_type', Doing::class)->get()->map(function ($doingType) {
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
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function edit(Question $question)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Question $question)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function destroy(Question $question)
    {
        // delete doing_question records
        $question->doings()->detach();

        // delete question
        $question->delete();

        return redirect()->route('questions.index')->with('success', 'Klausimas sėkmingai ištrintas');
    }
}
