<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pivots\Dutiable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class DutiableController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Dutiable::class, 'dutiable');
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Dutiable  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function show(Dutiable $dutiable)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Dutiable  $dutiable
     * @return \Illuminate\Http\Response
     */
    public function edit(Dutiable $dutiable)
    {
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
        //
    }
}
