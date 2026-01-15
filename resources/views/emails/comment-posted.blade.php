<x-mail::message>
**{{ $commenter['name'] }}** {{ __('notifications.commented_on') }} **{{ $object['name'] }}**:

<x-mail::panel>
{!! $commentText !!}
</x-mail::panel>

<x-mail::button :url="$object['url']">
{{ __('notifications.action_view_comment') }}
</x-mail::button>

{{ __('Ačiū') }},<br>
{{ config('app.name') }}
</x-mail::message>
