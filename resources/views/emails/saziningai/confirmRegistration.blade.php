@component('mail::message')
# Atsiskaitymo užregistravimo patvirtinimas

Sveiki,

norime pranešti, kad Jūs sėkmingai užregistravote atsiskaitymą „Sąžiningai“ registracijos platformoje!

## Registracijos informacija

- Atsiskaitymo dalyko pavadinimas: {{ $saziningai->name }}
- Atsiskaitymo tipas: {{ $saziningai->exam_type }}
- Padalinys: {{ strtoupper($saziningai->padalinys->shortname_vu) }}
- Atsiskaitymo laikymo vieta: {{ $saziningai->place }}
- Pirmo srauto laikymo pradžia: {{ $saziningaiFlow->start_time }}
- Atsiskaitymo trukmė: {{ $saziningai->duration }}
- Atsiskaitymo laikančių studentų skaičius: {{ $saziningai->students_need }}

Savo užregistruotą atsiskaitymą galite matyti [čia]({{ route('saziningaiExams.registered') }}).

Jeigu kokia nors informacija yra neteisinga arba pasikeitusi, prašome susisiekti su [saziningai@vusa.lt](mailto:saziningai@vusa.lt).

Taip pat, labai džiaugiamės, kad prisidedate prie Sąžiningai programos! 
Akademinio sąžiningumo temą universitete vertiname labai rimtai ir pagalbos reikia visada. 
Jeigu turite pažįstamų, kuriuos (-as) domina VU SA programos „Sąžiningai“ veikla, domintų stebėti ar registruoti egzaminus, būtume dėkingi, jeigu paskatintumėte juos (-as) prisidėti.

Ačiū, <br>
{{ config('app.name') }}
@endcomponent
