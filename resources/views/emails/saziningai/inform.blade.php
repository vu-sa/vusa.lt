@component('mail::message')
# Užregistruotas egzaminas

Buvo užregistruotas egzaminas vusa.lt sistemoje. 

Vardas: {{ $saziningai->name }}

El. paštas: {{ $saziningai->contact }}

Atsiskaitymo padalinys: {{ $saziningai->padalinys }}

Atsiskaitymo data: {{ $saziningai->time }}

@component('mail::button', ['url' => 'https://vusa.lt/admin/exam'])
Eiti į VU SA admin
@endcomponent

{{ config('app.name') }}
@endcomponent
