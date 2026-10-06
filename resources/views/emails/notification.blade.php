<x-mail::message>
# {{ $title }}

{{ $body }}

<x-mail::context :rows="$context" />

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

@if ($secondaryAction)
[{{ $secondaryAction['label'] }}]({{ $secondaryAction['url'] }})
@endif

<x-slot:subcopy>
{{ __('notifications.mail.sign_off') }}

{{ __('notifications.mail.why_received', ['category' => $category]) }} [{{ __('notifications.mail.settings_link') }}]({{ $settingsUrl }})
</x-slot:subcopy>
</x-mail::message>
