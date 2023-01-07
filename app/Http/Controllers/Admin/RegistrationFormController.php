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
        // get registrations for admin or user

        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $registrations = $registrationForm->load('registrations')->registrations;
        } else {
            $registrations = $registrationForm->load(['registrations' => function($query) {
                $query->where('data->whereToRegister', request()->user()->padalinys()->id);
            }])->registrations;
        }
        
        // for now, is accustomed to show only member registration
        return Inertia::render('Admin/Registrations/ShowRegistrationForm', [
            'registrationForm' => $registrations->sortByDesc('created_at')->values()->paginate(20)
        ]);
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
