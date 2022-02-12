@component('mail::message')
# Informacija apie užsiregistravusį stebėtoją

Užsiregistravusio vardas: {{ $saziningai_people->name_p }}

Contact_p: {{ $saziningai_people->contact_p }}

Egzaminas, į kurį užsiregistruota: {{ $saziningai->subject_name }}

@component('mail::button', ['url' => 'http://vusa.lt/admin/examPeople/' . $saziningai_people->id . '/edit'])
Eiti į vusa.lt admin
@endcomponent

{{ config('app.name') }}
@endcomponent