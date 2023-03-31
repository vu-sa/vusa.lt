<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DutiableController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Dutiable::class, $this->authorizer]);
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Dutiable::class, $this->authorizer]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Dutiable::class, $this->authorizer]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Dutiable  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function show(Dutiable $dutiable)
    {
        $this->authorize('view', [Dutiable::class, $dutiable, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function edit(Duty $duty, $dutiable)
    {
        $this->authorize('update', [Duty::class, $duty, $this->authorizer]);

        $dutiable = Dutiable::with('user', 'duty')
            ->where('duty_id', $duty->id)
            ->where('dutiable_id', $dutiable)->first();

        return Inertia::render('Admin/People/EditDutiable', [
            'dutiable' => $dutiable,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * TODO: this will not work for contacts
     *
     * @param  \App\Models\Dutiable  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function update(Duty $duty, $dutiable, Request $request)
    {
        $this->authorize('update', [Duty::class, $duty, $this->authorizer]);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'extra_attributes' => 'nullable|array',
        ]);

        $duty->users()->updateExistingPivot($dutiable, $validated);

        return back()->with('success', 'Pareigybė sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Dutiable  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dutiable $dutiable)
    {
        $this->authorize('delete', [Dutiable::class, $dutiable, $this->authorizer]);

        $dutiable->delete();

        return back()->with('success', 'Pareigybės laikotarpis sėkmingai ištrintas!');
    }
}
