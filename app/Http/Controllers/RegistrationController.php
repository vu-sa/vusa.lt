<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Collection;

class RegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegistrationRequest $request, Form $form)
    {
        $registration = new Registration;

        $registration->form()->associate($form);

        $registration->user_id = $request->validated()['user_id'];

        $formData = $request->validated()['data'];
        $fieldResponses = new Collection;

        collect($formData)->each(function ($value, $key) use ($form, $fieldResponses) {
            // key represents the form field id, value is the user input

            // check if the form field exists and belongs to the form
            try {
                $formField = FormField::query()->where('id', $key)
                    ->where('form_id', $form->id)
                    ->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return back()->with('error', 'Įvyko nenumatyta klaida. Bandykite dar kartą.');
            }

            $fieldResponse = $formField->fieldResponses()->make([
                'response' => $value,
            ]);

            $fieldResponses->push($fieldResponse);
        });

        $registration->save();

        $registration->fieldResponses()->saveMany($fieldResponses);

        return back()->with('success', 'Registracija sėkmingai išsiųsta!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Registration $registration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Registration $registration)
    {
        //
    }
}
