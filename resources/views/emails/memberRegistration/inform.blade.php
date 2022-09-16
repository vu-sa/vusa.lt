@component('mail::message')
# Informacija apie užpildytą registraciją

Gaunate šį laišką, nes [VU SA registracijos puslapyje](https://vusa.lt/nariu-registracija) buvo užpildyta registracija į {{ $registerLocation }}.

- Vardas: {{ $registration['name'] }}
- El. paštas: {{ $registration['email'] }}
- Telefono numeris: {{ $registration['phone'] }}
- Kursas: {{ $registration['course'] }}

Atitinkamą laišką gavo ir užsiregistravęs asmuo, kuriam (-ai) buvo pranešta, kad su juo bus susisiekta. 😊

@endcomponent
