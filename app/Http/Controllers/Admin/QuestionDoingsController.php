<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doing;
use App\Http\Controllers\Controller as Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionDoingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function index(Question $question)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function create(Question $question)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Question $question)
    {
        $validated = $request->validate([
            'title' => 'required',
            'doing_type_id' => 'required',
        ]);

        // add status "Sukurtas" to validated
        $validated['status'] = 'Sukurtas';
        $validated['question_id'] = $question->id;
        $validated['date'] = now();


        $doing = Doing::create($validated);

        return redirect()->route('questions.doings.show', [$question, $doing])->with('success', 'Veiksmas sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function show(Question $question, Doing $doing)
    {
        return Inertia::render('Admin/Questions/ShowDoing', [
            'question' => $question->load('institution'),
            'doing' => $doing,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function edit(Question $question, Doing $doing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Question $question, Doing $doing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function destroy(Question $question, Doing $doing)
    {
        //
    }
}
