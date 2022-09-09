@component('mail::message')
# Patvirtinimas apie atsiskaitymo stebėjimo užsiregistravimą

Norime pranešti, kad sėkmingai užsiregistravote į atsiskaitymo stebėjimą.

## Informacija apie atsiskaitymą

- Dalyko pavadinimas: {{ $saziningaiFlow->exam->subject_name }}
- Laikymo vieta: {{ $saziningaiFlow->exam->place }}
- Atsiskaitymo pradžia: {{ $saziningaiFlow->start_time }}

Labai prašome nevėluoti į atsiskaitymo pradžią!

{{-- Labai prašome prieš atsiskaitymą susipažinti su [stebėtojo atmintine](https://vusa.lt).  --}}
Atsiradus kokiems nors pokyčiams dėl stebėjimo galimybės, labai prašome informuoti el. paštu [saziningai@vusa.lt](mailto:saziningai@vusa.lt).

Ačiū, kad padedate programai „Sąžiningai“ augti 💗<br>
{{ config('app.name') }}
@endcomponent
