@component('mail::message')
# Patvirtinimas į atsiskaitymo užsiregistravimą

Norime pranešti, kad sėkmingai užsiregistravote į atsiskaitymo stebėjimą.

## Informacija apie atsiskaitymą

Dalyko pavadinimas: {{ $saziningai->subject_name }}

Laikymo vieta: {{ $saziningai->place }}

Visi egzamino srautai: {{ $saziningai->time }}

Srauto numeris, į kurį užsiregistruota: {{ $saziningai_people->flow }}

Labai prašome prieš atsiskaitymą susipažinti su [stebėtojo atmintine](https://vusa.lt). Atsiradus kokiems nors pokyčiams dėl stebėjimo galimybės, labai prašome informuoti el. paštu [saziningai@vusa.lt](mailto:saziningai@vusa.lt).

Ačiū, kad padedate programai „Sąžiningai“ augti 💗<br>
{{ config('app.name') }}
@endcomponent
