@php($plainLinks = fn ($text) => preg_replace('/\[([^\]]+?)\.?\]\((https?:[^)]+)\)/', '$1: $2', strip_tags($text)))
{!! strip_tags($header ?? '') !!}

{!! $plainLinks($slot) !!}
@isset($subcopy)

{!! $plainLinks($subcopy) !!}
@endisset

{!! strip_tags($footer ?? '') !!}
