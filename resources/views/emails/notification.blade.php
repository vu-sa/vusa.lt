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
@if ($signature)
{{ __('notifications.mail.signature_intro') }} {{ $signature['name'] }}@if ($signature['duty']), {{ $signature['duty'] }}@endif · [{{ $signature['email'] }}](mailto:{{ $signature['email'] }})
@else
{{ __('notifications.mail.sign_off') }}
@endif

{{ __('notifications.mail.why_received', ['category' => $category]) }} [{{ __('notifications.mail.settings_link') }}]({{ $settingsUrl }})
</x-slot:subcopy>
</x-mail::message>
