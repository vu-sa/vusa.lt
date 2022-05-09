@component('mail::message')
# Atsiskaitymo užregistravimo patvirtinimas

Sveiki,

norime pranešti, kad Jūs sėkmingai užregistravote egzaminą Sąžiningai registracijos platformoje

## Registracijos informacija

Egzamino tipas: {{ $saziningai->exam }}

Padalinys: {{ strtoupper($saziningai->padalinys) }}

Egzamino laikymo vieta: {{ $saziningai->place }}

Egzamino laikymo laikas (laikai): {{ $saziningai->time }}

Egzamino trukmė (minutės): {{ $saziningai->duration }}

Egzaminą laikančių studentų skaičius: {{ $saziningai->count }}

Jeigu kokia nors informacija yra neteisinga arba pasikeitusi, prašome susisiekti su [saziningai@vusa.lt](mailto:saziningai@vusa.lt).

Taip pat, labai džiaugiamės, kad prisidedate prie Sąžiningai programos! 
Akademinio sąžiningumo temą universitete vertiname labai rimtai ir pagalbos reikia visada. 
Jeigu turite pažįstamų, kuriuos (-as) domina VU SA programos "Sąžiningai" veikla, domintų stebėti ar registruoti egzaminus, būtume dėkingi, jeigu paskatintumėte juos (-as) prisidėti.

Ačiū, <br>
{{ config('app.name') }}
@endcomponent
