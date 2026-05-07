<?php

namespace App\Exports;

use App\Models\Form;
use App\Support\Spreadsheet\SpreadsheetWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormRegistrationsExport
{
    public function __construct(public Form $form) {}

    public function download(string $filename): StreamedResponse
    {
        return SpreadsheetWriter::downloadXlsx($this->rows(), $filename);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function rows(): array
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

                $responseValue = $fieldResponse?->getValue();

                $registrationArray[] = $responseValue;
            });

            $finalArray[] = $registrationArray;
        });

        return $finalArray;
    }
}
