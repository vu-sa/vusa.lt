<?php

namespace App\Http\Controllers;

use App\Events\MemberRegistrationCreated;
use App\Events\StudentRepRegistrationCreated;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Registration;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegistrationRequest $request, Form $form)
    {
        // Check if form is published
        // If publish_time is null, form is available by default
        // If publish_time is set and in the future, block access
        if ($form->publish_time && \Carbon\Carbon::parse($form->publish_time)->isFuture()) {
            abort(403, 'Form is not yet published');
        }

        try {
            return DB::transaction(function () use ($request, $form) {
                $registration = new Registration;
                $registration->form()->associate($form);

                if ($request->has('user_id')) {
                    $registration->user_id = $request->validated()['user_id'];
                } elseif ($request->user()) {
                    $registration->user_id = $request->user()->id;
                }

                $formData = $request->validated()['data'];
                $fieldResponses = new Collection;

                // Use foreach instead of collect()->each() so return statements work properly
                foreach ($formData as $key => $fieldData) {
                    // key represents the form field id, fieldData is the array with 'value' key
                    // Ensure consistent type casting for field IDs
                    $fieldId = (string) $key;

                    // check if the form field exists and belongs to the form
                    $formField = FormField::query()
                        ->where('id', $fieldId)
                        ->where('form_id', $form->id)
                        ->first();

                    if (! $formField) {
                        Log::error('Form field not found', [
                            'field_id' => $fieldId,
                            'form_id' => $form->id,
                            'available_fields' => $form->formFields()->pluck('id')->toArray(),
                        ]);

                        return back()->with('error', 'Įvyko nenumatyta klaida. Bandykite dar kartą.');
                    }

                    // Extract the value from the field data array
                    $responseValue = $fieldData['value'] ?? null;

                    $fieldResponse = $formField->fieldResponses()->make([
                        'response' => ['value' => $responseValue],
                    ]);

                    $fieldResponses->push($fieldResponse);
                }

                // Save registration first
                $registration->save();

                // Save all field responses
                $registration->fieldResponses()->saveMany($fieldResponses);

                // Dispatch event if needed
                MemberRegistrationCreated::dispatchIf($form->id === app(FormSettings::class)->member_registration_form_id, $registration);

                // Dispatch student rep registration event if applicable
                $this->dispatchStudentRepRegistrationEvent($form, $registration, $fieldResponses);

                Log::info('Registration saved successfully', [
                    'registration_id' => $registration->id,
                    'form_id' => $form->id,
                    'field_responses_count' => $fieldResponses->count(),
                ]);

                return back()->with([
                    'success' => 'Registracija sėkmingai išsiųsta!',
                    'toast_description' => 'Patikrinkite savo el. paštą.',
                    'toast_duration' => 8000, // 8 seconds for important registration message
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Registration save failed', [
                'error' => $e->getMessage(),
                'form_id' => $form->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Įvyko nenumatyta klaida. Bandykite dar kartą.');
        }
    }

    /**
     * Dispatch the student rep registration event if the form matches the setting.
     */
    private function dispatchStudentRepRegistrationEvent(Form $form, Registration $registration, Collection $fieldResponses): void
    {
        $formSettings = app(FormSettings::class);

        if ($form->id !== $formSettings->student_rep_registration_form_id) {
            return;
        }

        // Find the institution field in the form
        $institutionField = $form->formFields->first(function ($field) {
            return $field->use_model_options && $field->options_model === Institution::class;
        });

        if (! $institutionField) {
            Log::warning('Student rep registration form missing institution field', [
                'form_id' => $form->id,
            ]);

            return;
        }

        // Find the institution from field responses
        $institutionResponse = $fieldResponses->first(function ($fieldResponse) use ($institutionField) {
            return $fieldResponse->formField->id === $institutionField->id;
        });

        if (! $institutionResponse) {
            Log::warning('Student rep registration missing institution response', [
                'form_id' => $form->id,
                'registration_id' => $registration->id,
            ]);

            return;
        }

        $institutionId = null;

        if (is_array($institutionResponse->response) && array_key_exists('value', $institutionResponse->response)) {
            $institutionId = $institutionResponse->response['value'];
        }

        $institution = Institution::find($institutionId);

        if (! $institution) {
            Log::warning('Student rep registration institution not found', [
                'form_id' => $form->id,
                'registration_id' => $registration->id,
                'institution_id' => $institutionId,
            ]);

            return;
        }

        StudentRepRegistrationCreated::dispatch($registration, $institution);
    }
}
