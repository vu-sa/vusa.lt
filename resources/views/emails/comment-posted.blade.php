<x-mail::message>

**{{ $commenter['name'] }}** {{ __('pateikė komentarą') }}:

<x-mail::panel>
{!! $commentText !!}
</x-mail::panel>

**{{ __('Objektas') }}:** {{ $object['name'] }}

<x-mail::button :url="$object['url']">
{{ __('Peržiūrėti') }}
</x-mail::button>

</x-mail::message>
