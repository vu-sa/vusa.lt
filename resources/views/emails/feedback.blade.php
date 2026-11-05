<x-mail::message>

@if ($href)
**{{ __('mail.feedback.page') }}:** {{ $href }}
@endif

@if ($selectedText)
**{{ __('mail.feedback.selected_text') }}:** {{ $selectedText }}
@endif

**{{ __('mail.feedback.body') }}:** {{ $feedback }}

@if ($user)
{{ $user->name }} {{ $user->email }}
@endif

</x-mail::message>
