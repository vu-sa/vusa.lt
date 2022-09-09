@component('mail::message')
# Užregistruotas egzaminas

Buvo užregistruotas egzaminas vusa.lt sistemoje. 

- Vardas: {{ $saziningai->name }}
- El. paštas: {{ $saziningai->email }}
- Atsiskaitymo padalinys: {{ $saziningai->padalinys->shortname }}
- Atsiskaitymo data: {{ $saziningaiFlow->start_time }}

@component('mail::button', ['url' => route('saziningaiExams.edit', $saziningai->id)])
Eiti į VU SA admin
@endcomponent

@endcomponent
