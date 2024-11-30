<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MemberRegistrationFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $form = new \App\Models\Form;
        $form->setTranslation('name', 'lt', 'Prašymas tapti VU SA (arba VU SA PKP) nariu');
        $form->setTranslation('name', 'en', 'Application to become a member of VU SA (or VU SA PKP)');
        $form->setTranslation('description', 'lt', '<p><strong>Kiekvienas VU studentas gali tapti VU SA nariu!<strong> užsiregistruok ir lauk pakvietimo iš padalinio komandos!</p><p>Taip pat gali registruotis ir į mūsų programas, klubus ir projektus (PKP)!</p>');
        $form->setTranslation('description', 'en', '<p><strong>Every VU student can become a member of VU SA!<strong> register and wait for an invitation from the division team!</p><p>You can also register for our programs, clubs and projects (PKP)!</p>');
        $form->setTranslation('path', 'lt', 'nariu-registracija');
        $form->setTranslation('path', 'en', 'member-registration');
        $form->save();

        $nameField = new \App\Models\FormField;
        $nameField->setTranslation('label', 'lt', 'Vardas ir pavardė');
        $nameField->setTranslation('label', 'en', 'Name and surname');
        $nameField->type = 'string';
        $nameField->is_required = true;
        $nameField->order = 0;

        $nameField->form()->associate($form);
        $nameField->save();

        $emailField = new \App\Models\FormField;
        $emailField->setTranslation('label', 'lt', 'El. paštas');
        $emailField->setTranslation('label', 'en', 'Email');
        $emailField->type = 'string';
        $emailField->subtype = 'email';
        $emailField->is_required = true;
        $emailField->order = 1;

        $emailField->form()->associate($form);
        $emailField->save();

        $phoneField = new \App\Models\FormField;
        $phoneField->setTranslation('label', 'lt', 'Telefono numeris');
        $phoneField->setTranslation('label', 'en', 'Phone number');
        $phoneField->type = 'string';
        $phoneField->is_required = true;
        $phoneField->order = 2;

        $phoneField->form()->associate($form);
        $phoneField->save();

        $tenantField = new \App\Models\FormField;
        $tenantField->setTranslation('label', 'lt', 'Kur nori užregistruoti?');
        $tenantField->setTranslation('label', 'en', 'Where do you want to register?');
        $tenantField->type = 'enum';
        $tenantField->is_required = true;
        $tenantField->order = 3;
        $tenantField->options = [
            ['value' => 16, 'label' => ['lt' => 'VU SA', 'en' => 'VU SR']],
            ['value' => 17, 'label' => ['lt' => 'VU SA PKP', 'en' => 'VU SR PCP']],
        ];

        $tenantField->form()->associate($form);
        $tenantField->save();

        $registrationField = new \App\Models\FormField;
        $registrationField->setTranslation('label', 'lt', 'Studijų kursas');
        $registrationField->setTranslation('label', 'en', 'Study course');
        $registrationField->type = 'enum';
        $registrationField->is_required = true;
        $registrationField->order = 4;
        $registrationField->options = [
            ['value' => 1, 'label' => ['lt' => 1, 'en' => 1]],
            ['value' => 2, 'label' => ['lt' => 2, 'en' => 2]],
            ['value' => 3, 'label' => ['lt' => 3, 'en' => 3]],
            ['value' => 4, 'label' => ['lt' => 4, 'en' => 4]],
            ['value' => 5, 'label' => ['lt' => 5, 'en' => 5]],
            ['value' => 6, 'label' => ['lt' => 6, 'en' => 6]],
        ];

        $registrationField->form()->associate($form);
        $registrationField->save();

        // TODO: Add field of activity field

        $gdprField = new \App\Models\FormField;

        $gdprField->setTranslation('label', 'lt', 'Susipažinau su Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašu ir sutinku');
        $gdprField->setTranslation('label', 'en', 'I have read the description of the processing of personal data at the Vilnius University Student Representation and agree');

        $gdprField->type = 'boolean';
        $gdprField->is_required = true;
        $gdprField->order = 5;

        $gdprField->form()->associate($form);
        $gdprField->save();

        $privacyField = new \App\Models\FormField;

        $privacyField->setTranslation('label', 'lt', 'Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašą');
        $privacyField->setTranslation('label', 'en', 'I agree that my personal data provided will be processed for internal administration purposes according to the description of the processing of personal data at the Vilnius University Student Representation');

        $privacyField->type = 'boolean';
        $privacyField->is_required = true;
        $privacyField->order = 6;

        $privacyField->form()->associate($form);
        $privacyField->save();
    }
}
