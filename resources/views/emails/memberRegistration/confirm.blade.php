@component('mail::message')
# Patvirtinimas apie sėkmingą užsiregistravimą ✅

Sveiki, {{ $registration['name'] }}! 

## Registracijos informacija

- Užsiregistravote į: {{ $registerLocation }}
- Iš ko tikėtis atsakymo: [{{ $chairPerson->name ?? $chairPerson->email }}](mailto:{{ $chairPerson->email }})

Palaukite tolimesnio atsakymo, o jeigu jo nesulauksite per kelias dienas, galite susisiekti tiesiogiai, atsakant į šį laišką.

Iki susitikimo! 👋
{{ config('app.name') }}
@endcomponent
