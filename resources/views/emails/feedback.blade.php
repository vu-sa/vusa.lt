<x-mail::message>

@if ($href)
**Puslapis:** {{ $href }}
@endif

@if ($selectedText)
**Pasirinktas tekstas:** {{ $selectedText }}
@endif

**Atsiliepimas:** {{ $feedback }}

@if ($user)
{{ $user->name }} {{ $user->email }}
@endif

</x-mail::message>
