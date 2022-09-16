@component('mail::message')
# Informacija apie užsiregistravusį stebėtoją

- Užsiregistravusio vardas: {{ $saziningai_people->name }}
- El. paštas: {{ $saziningai_people->email }}
- Tel. nr: {{ $saziningai_people->phone }}
- Egzaminas, į kurį užsiregistruota: {{ $saziningaiFlow->exam->subject_name }}

@component('mail::button', ['url' => route('saziningaiExams.edit', $saziningaiFlow->exam->id)])
Eiti į vusa.lt admin
@endcomponent
@endcomponent