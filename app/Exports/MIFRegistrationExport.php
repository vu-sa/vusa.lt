<?php

namespace App\Exports;

use App\Models\Registration;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MIFRegistrationExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Vardas ir pavardė',
            'Įvardžiai',
            'Telefono numeris',
            'El. paštas',
            'Gimimo data milisekundėmis',
            'Studijų programa',
            'Priminimas dėl kainos',
            '4 dienos?',
            'Transportas',
            'Ką valgo?',
            'Alergijos',
            'Specialūs poreikiai',
            'Papildoma informacija organizatoriams',
            'Įdomiausias faktas',
            'Eurovizija 1',
            'Eurovizija 2',
            'Eurovizija 3',
            'Lenktynės',
            'Verkas Serdiučka',
            'Privatumo politika',
            'GDPR',
            'Tikroji gimimo data',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function array(): array
    {
        // return all registrations and map data json to array
        $registrations = Registration::all();

        $export = [];

        foreach ($registrations as $registration) {
            // change birthdate from unix time to human readable date
            $data = $registration->data;

            $birthdate = date('Y-m-d', $data['birthDate'] / 1000);
            $data['realBirthDate'] = $birthdate;

            $export[] = $data;
        }

        return $export;
    }
}
