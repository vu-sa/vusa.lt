<?php

namespace App\Http\Controllers;

use App\Events\MemberRegistrationCreated;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Registration;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Collection;

class RegistrationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegistrationRequest $request, Form $form)
    {
        // Check if form is published
        if (! $form->publish_time || \Carbon\Carbon::parse($form->publish_time)->isFuture()) {
            abort(403, 'Form is not yet published');
        }

        $registration = new Registration;

        $registration->form()->associate($form);

        if ($request->has('user_id')) {
            $registration->user_id = $request->validated()['user_id'];
        } elseif ($request->user()) {
            $registration->user_id = $request->user()->id;
        }

        $formData = $request->validated()['data'];
        $fieldResponses = new Collection;

        collect($formData)->each(function ($fieldData, $key) use ($form, $fieldResponses) {
            // key represents the form field id, fieldData is the array with 'value' key

            // check if the form field exists and belongs to the form
            try {
                $formField = FormField::query()->where('id', $key)
                    ->where('form_id', $form->id)
                    ->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return back()->with('error', 'Įvyko nenumatyta klaida. Bandykite dar kartą.');
            }

            // Extract the value from the field data array
            $responseValue = $fieldData['value'] ?? null;

            $fieldResponse = $formField->fieldResponses()->make([
                'response' => ['value' => $responseValue],
            ]);

            $fieldResponses->push($fieldResponse);
        });

        $registration->save();

        $registration->fieldResponses()->saveMany($fieldResponses);

        MemberRegistrationCreated::dispatchIf($form->id === app(FormSettings::class)->member_registration_form_id, $registration);

        return back()->with('success', 'Registracija sėkmingai išsiųsta!');
    }
}
