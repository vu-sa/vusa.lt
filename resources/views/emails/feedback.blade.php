<x-mail::message>

{{ $feedback }}

@if ($user)
{{ $user->name }} {{ $user->email }}
@endif

</x-mail::message>
