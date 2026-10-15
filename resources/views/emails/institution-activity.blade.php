<x-mail::message>
# {{ $title }}

{{ \App\Support\MailGreeting::forName($recipientName ?? null) }}

{{ $body }}

<x-mail::context :rows="$context" />

@if ($note !== null)
<x-mail::panel>
{{ $note }}
</x-mail::panel>
@endif

@foreach ($questions as $question)
<x-mail::activity-question :question="$question" />

@endforeach

<x-slot:subcopy>
{{ __('notifications.mail.sign_off') }}

{{ __('notifications.mail.why_received', ['category' => $category]) }} [{{ __('notifications.mail.settings_link') }}]({{ $settingsUrl }})
</x-slot:subcopy>
</x-mail::message>
