@component('mail::message')
# Informacija apie užpildytą registraciją

Labas! Gavai šį laišką, nes [VU SA registracijos puslapyje]({{ route('member-registration') }}) {{ $name }} užpildė registraciją į *{{ $institution->name }}*.

Visą registracijos informaciją gali pamatyti [čia]({{ route('registrations.show', $registration_id) }}).

Atitinkamą laišką gavo ir užsiregistravęs asmuo, kuriam (-ai) buvo pranešta, kad su juo bus susisiekta.

Sėkmės!
@endcomponent
