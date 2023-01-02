<?php

namespace App\Http\Controllers\Admin;

use App\Models\DutyUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class DutyUserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DutyUser::class, 'dutyUser');
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
     * @param  \App\Models\DutyUser  $dutyUser
     * @return \Illuminate\Http\Response
     */
    public function show(DutyUser $dutyUser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DutyUser  $dutyUser
     * @return \Illuminate\Http\Response
     */
    public function edit(DutyUser $dutyUser)
    {
        return Inertia::render('Admin/Contacts/EditDutyUser', [
            'dutyUser' => $dutyUser,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DutyUser  $dutyUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DutyUser $dutyUser)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'attributes' => 'nullable|array'
        ]);

        $dutyUser->update($validated);
        
        return back()->with('success', 'Pareigybė sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DutyUser  $dutyUser
     * @return \Illuminate\Http\Response
     */
    public function destroy(DutyUser $dutyUser)
    {
        //
    }
}
