<?php

namespace App\Http\Controllers\Admin;

use App\Models\QuestionGroup;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionGroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(QuestionGroup::class, 'questionGroup');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $search = request()->input('search');

        $questionGroups = QuestionGroup::when(!request()->user()->hasRole('Super Admin'), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%")->orWhere('alias', 'like', "%{$search}%");
        })->paginate(20);

        return Inertia::render('Admin/Questions/IndexQuestionGroups', [
            'questionGroups' => $questionGroups,
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

        $questionGroup = QuestionGroup::create($validated);

        return back()->with('success', 'Klausimo grupė sukurta')->with('data', $questionGroup->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\QuestionGroup  $questionGroup
     * @return \Illuminate\Http\Response
     */
    public function show(QuestionGroup $questionGroup)
    {
        $questionGroup->load('questions.doings', 'questions.institution:id,name');

        $institutions = $questionGroup->questions->pluck('institution')->unique('id');

        return Inertia::render('Admin/Questions/ShowQuestionGroup', [
            'questionGroup' => $questionGroup,
            'institutions' => $institutions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\QuestionGroup  $questionGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(QuestionGroup $questionGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\QuestionGroup  $questionGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, QuestionGroup $questionGroup)
    {
        $validated = $request->validate(
            ['title' => 'required']
        );

        $questionGroup->update($validated);

        return back()->with('success', 'Klausimo grupė atnaujinta');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\QuestionGroup  $questionGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy(QuestionGroup $questionGroup)
    {
        $questionGroup->delete();

        return redirect()->route('dashboard')->with('success', 'Klausimo grupė ištrinta.');
    }
}
