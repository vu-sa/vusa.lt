<x-mail::message>

<small>🇬🇧 English below</small>

Labas, **{{ $addressivizedName }}**! 👋

Rašome tau, nes mūsų duomenimis esi VU SA studentų atstovas (-ė) 
@if ($institutionLtNames->count() > 1)
šiuose atstovavimo organuose:
@else
šiame atstovavimo organe:
@endif

@foreach ($institutionLtNames as $institution)
- {{ $institution }}
@endforeach

Norime tik priminti, kad **prisijungtum prie [VU SA atstovavimo platformos]({{ config('app.url') }}/mano)** ir supildytum, kas vyko per Tavo atstovavimo laikotarpį. 

Studentų atstovavimas remiasi kiekvieno (-os) studentų atstovo (-ės) aktyviu įsitraukimu ir atsakomybe už tinkamą informacijos perdavimą. Todėl tikimės, kad:

1. ✅ Sužymėsi į kalendorių per atstovavimo laikotarpį įvykusius ar vyksiančius **posėdžius**.
2. ✅ Įkelsi **svarstytus klausimus** ir sužymėsi, kokie sprendimai buvo priimti.
3. ✅ Sukelsi posėdžių **ataskaitas, protokolus** bei kitus aktualius dokumentus, kurie, tavo manymu, turėtų būti perduoti ateities atstovų kartoms.

Tai, ką įkelsi bus matoma ne tik dabartiniams, bet ir būsimiems studentų atstovams, todėl **Tavo indėlis padės užtikrinti sklandų atstovavimo procesą bei patirties perdavimą**! 🏛️

<x-mail::button :url="config('app.url') . '/mano/dashboard/atstovavimas'">
Prisijungti prie vusa.lt/mano
</x-mail::button>

Jeigu turi klausimų, drąsiai kreipkis į savo atstovų koordinatorių (-ę) arba pasiskaityk **[D.U.K. sekciją VU SA dokumentacijoje]({{ config('app.url') }}/docs/faq.html)**.

<small>p.s. Jeigu manai, kad gavai šį laišką per klaidą, pranešk mums el. paštu: [{{ config('vusa.contacts.it') }}](mailto:{{ config('vusa.contacts.it') }}). Laiškas buvo sugeneruotas automatiškai pagal vusa.lt/mano esančią informaciją.</small>

Ačiū ir Vieningai Už Studentų Ateitį,<br>
{{ config('app.name') }} ❤️  💛

---

Hello, **{{ $addressivizedName }}**! 👋

We are writing to you because you are a VU student representative in this institution / these institutions:

@foreach ($institutionEnNames as $institution)
- {{ $institution }}
@endforeach

We just want to remind you that student representation is based on the active involvement and responsibility of each student representative for the proper transmission of information. Therefore, we hope that:

1. ✅ You will mark in the calendar the meetings that have taken place or will take place during the representation period.
2. ✅ You will upload the issues discussed and mark what decisions were made.
3. ✅ You will upload meeting reports, protocols, and other relevant documents that, in your opinion, should be passed on to future generations of representatives.

What you upload will be visible not only to current, but also to future student representatives, so **your contribution will help ensure a smooth representation process**! 🏛️

<x-mail::button :url="config('app.url') . '/mano/dashboard/atstovavimas'">

Log in to vusa.lt/mano

</x-mail::button>

If you have any questions, feel free to contact your representative coordinator or read the **[FAQ section in the VU SA documentation]({{ config('app.url') }}/docs/en/faq.html)**.

<small>p.s. If you think you received this email by mistake, please let us know by email: [{{ config('vusa.contacts.it') }}](mailto:{{ config('vusa.contacts.it') }})</small>

Thank you and United For The Future Of Students,<br>
{{ config('app.name') }} ❤️  💛

</x-mail::message>
