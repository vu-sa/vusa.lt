<?php

namespace App\Http\Controllers\Admin;

use App\Models\RegistrationForm;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistrationFormController extends Controller
{
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
        return Inertia::render('Admin/Registrations/CreateRegistrationForm');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        dd($request->all());
        
        $registrationForm = new RegistrationForm();
        $registrationForm->data = $request->data;
        $registrationForm->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RegistrationForm  $registrationForm
     * @return \Illuminate\Http\Response
     */
    public function show(RegistrationForm $registrationForm)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RegistrationForm  $registrationForm
     * @return \Illuminate\Http\Response
     */
    public function edit(RegistrationForm $registrationForm)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RegistrationForm  $registrationForm
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RegistrationForm $registrationForm)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RegistrationForm  $registrationForm
     * @return \Illuminate\Http\Response
     */
    public function destroy(RegistrationForm $registrationForm)
    {
        //
    }
}
