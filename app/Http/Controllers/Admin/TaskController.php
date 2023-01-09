<?php

namespace App\Http\Controllers\Admin;

use App\Models\Task;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        $validated = $request->validate([
            'name' => 'required',
            'due_date' => 'required',
        ]);

        // if no taskable_type is provided, set it to App\\Models\\User and id to auth()->id()
        if (!isset($validated['taskable_type'])) {
        $validated['taskable_type'] = 'App\\Models\\User';
            $validated['taskable_id'] = auth()->id();
        }

        // change due_date to Carbon object
        $validated['due_date'] = Carbon::createFromTimestamp($validated['due_date'] / 1000);

        $task = Task::create($validated);

        $task->users()->attach(auth()->id());

        return back()->with('success', 'Užduotis sėkmingai pridėta');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function edit(Task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return back()->with('success', 'Užduotis sėkmingai ištrinta');
    }

    public function updateCompletionStatus(Request $request, Task $task)
    {      
        if ($request->completed == true) {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        return back()->with('success', 'Užduoties būsena sėkmingai atnaujinta');
    }
}
