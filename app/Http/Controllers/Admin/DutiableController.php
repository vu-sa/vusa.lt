<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pivots\Dutiable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;

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
     * @param  \Illuminate\Http\Request  $request
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
     * @param  \App\Models\Dutiable  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function edit(Dutiable $dutiable)
    {
        $this->authorize('update', [Dutiable::class, $dutiable, $this->authorizer]);
        
        return Inertia::render('Admin/People/EditDutiable', [
            'dutiable' => $dutiable,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Dutiable  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Dutiable $dutiable)
    {
        $this->authorize('update', [Dutiable::class, $dutiable, $this->authorizer]);
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'extra_attributes' => 'nullable|array'
        ]);

        $dutiable->update($validated);
        
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
