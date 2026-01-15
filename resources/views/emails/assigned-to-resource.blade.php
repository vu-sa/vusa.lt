<x-mail::message>
# {{ __('notifications.assigned_to_resource_greeting') }}

**{{ $assigner['name'] }}** {{ __('notifications.assigned_you_to') }} **{{ $resource['name'] }}**.

<x-mail::button :url="$resource['url']">
{{ __('notifications.action_view_resource') }}
</x-mail::button>

{{ __('Ačiū') }},<br>
{{ config('app.name') }}
</x-mail::message>
