<?php

namespace App\Exports;

use App\Models\Form;
use App\Models\User;
use App\Services\FormRegistrationVisibilityService;
use App\Support\Spreadsheet\SpreadsheetWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormRegistrationsExport
{
    public function __construct(public Form $form, public User $user) {}

    public function download(string $filename): StreamedResponse
    {
        return SpreadsheetWriter::downloadXlsx($this->rows(), $filename);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function rows(): array
    {
        $this->form->load('formFields');

        $registrations = app(FormRegistrationVisibilityService::class)
            ->query($this->form, $this->user)
            ->with('fieldResponses')
            ->orderBy('id')
            ->get();

        $finalArray = [];

        $formFieldLabelArray = $this->form->formFields->map(fn ($formField) => $formField->getTranslation('label', app()->getLocale()))->toArray();

        $finalArray[] = array_merge(['Registracijos ID'], $formFieldLabelArray);

        $registrations->each(function ($registration) use (&$finalArray): void {
            $registrationArray = [$registration->id];

            $this->form->formFields->each(function ($formField) use (&$registrationArray, $registration): void {
                $fieldResponse = $registration->fieldResponses->firstWhere('form_field_id', $formField->id);

                $responseValue = $fieldResponse?->getValue();

                $registrationArray[] = $responseValue;
            });

            $finalArray[] = $registrationArray;
        });

        return $finalArray;
    }
}
