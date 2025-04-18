<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of tasks.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $tasks = Task::with(['users', 'taskable'])
            ->whereHas('users', function ($query) {
                $query->where('users.id', Auth::id());
            })
            ->orderBy('completed_at', 'asc')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/ShowTasks', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * Return tasks for the current user in JSON format.
     * Used by the TasksIndicator component.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userTasksForIndicator(Request $request)
    {
        $limit = $request->input('limit', 5);

        $tasks = Task::with('taskable')
            ->whereHas('users', function ($query) {
                $query->where('users.id', Auth::id());
            })
            ->whereNull('completed_at')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTaskRequest $request)
    {
        // if separate_tasks is true, create separate tasks for each responsible person
        if ($request->separate_tasks) {
            foreach ($request->responsible_people as $responsible_person) {
                $task = Task::create($request->safe()->only('name', 'taskable_id', 'taskable_type', 'due_date'));
                $task->users()->attach($responsible_person);
            }
        } else {
            $task = Task::create($request->safe()->only('name', 'taskable_id', 'taskable_type', 'due_date'));
            $task->users()->attach($request->responsible_people);
        }

        return back()->with('success', 'Užduotis sėkmingai pridėta');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'name' => 'required',
            'due_date' => 'required',
        ]);

        // change due_date to Carbon object
        $validated['due_date'] = Carbon::createFromTimestamp($validated['due_date'] / 1000, 'Europe/Vilnius');

        $task->update($validated);

        return back()->with('success', 'Užduotis sėkmingai atnaujinta');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back()->with('success', 'Užduotis sėkmingai ištrinta');
    }

    public function updateCompletionStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        if ($request->completed == true) {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        return back()->with('success', 'Užduoties būsena sėkmingai atnaujinta');
    }
}
