<?php

namespace App\Exports;

use App\Models\Form;
use Maatwebsite\Excel\Concerns\FromArray;

class FormRegistrationsExport implements FromArray
{
    public function __construct(public Form $form) {}

    public function array(): array
    {
        $this->form->load('formFields', 'registrations.fieldResponses.formField');

        $finalArray = [];

        $formFieldLabelArray = $this->form->formFields->map(function ($formField) {
            return $formField->getTranslation('label', app()->getLocale());
        })->toArray();

        $finalArray[] = array_merge(['Registracijos ID'], $formFieldLabelArray);

        $this->form->registrations->each(function ($registration) use (&$finalArray) {
            $registrationArray = [$registration->id];

            $this->form->formFields->each(function ($formField) use (&$registrationArray, $registration) {
                $fieldResponse = $registration->fieldResponses->firstWhere('form_field_id', $formField->id);

                // Handle response as string or array with 'value' key
                $responseValue = null;
                if ($fieldResponse) {
                    $response = $fieldResponse->response;
                    if (is_array($response) && isset($response['value'])) {
                        $responseValue = $response['value'];
                    } elseif (is_string($response)) {
                        $responseValue = $response;
                    }
                }

                $registrationArray[] = $responseValue;
            });

            $finalArray[] = $registrationArray;
        });

        return $finalArray;
    }
}
