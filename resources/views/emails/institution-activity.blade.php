<x-mail::message>
# {{ $title }}

{{ $body }}

<x-mail::context :rows="$context" />

@foreach ($questions as $question)
<x-mail::activity-question :question="$question" />

@endforeach

{{ __('activity_requests.email.footer') }} [{{ __('activity_requests.open_mano') }}]({{ route('dashboard') }})

<x-slot:subcopy>
{{ __('notifications.mail.sign_off') }}

{{ __('notifications.mail.why_received', ['category' => $category]) }} [{{ __('notifications.mail.settings_link') }}]({{ $settingsUrl }})
</x-slot:subcopy>
</x-mail::message>
