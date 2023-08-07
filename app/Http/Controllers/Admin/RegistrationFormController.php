<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MIFRegistrationExport;
use App\Http\Controllers\LaravelResourceController;
use App\Models\Registration;
use App\Models\RegistrationForm;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class RegistrationFormController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [
            Institution::class,
            $this->authorizer,
        ]);

        return Excel::download(new MIFRegistrationExport, 'registration.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // TODO: Implement function or just remove it entirely.
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $registrationForm = new RegistrationForm();
        $registrationForm->data = $request->data;
        $registrationForm->save();

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(RegistrationForm $registrationForm)
    {
        // get registrations for admin or user

        $registrations = Registration::query()->where('registration_form_id', $registrationForm->id);

        if ($registrationForm->id === 2 && ! request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $registrations = $registrations
                ->whereIn('data->whereToRegister', request()->user()->padaliniai()->get(['padaliniai.id'])->pluck('id'));
        }

        // for now, is accustomed to show only member registration
        return Inertia::render('Admin/RegistrationForms/ShowRegistrationForm', [
            'registrations' => $registrations->paginate(35),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(RegistrationForm $registrationForm)
    {
        // TODO: Implement function or just remove it entirely.
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RegistrationForm $registrationForm)
    {
        // TODO: Implement function or just remove it entirely.
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(RegistrationForm $registrationForm)
    {
        // TODO: Implement function or just remove it entirely.
    }
}
