@component('mail::message')
# {{ __('mail.confirmRegistration1') }} ✅

{{ __('mail.greeting', ['name' => $registration['name'] ]) }}

## {{ __('mail.confirmRegistration2') }}

- {{ __('mail.confirmRegistration3') }}: {{ $registerLocation }}
- {{ __('mail.confirmRegistration4') }}: [{{ $chairPerson->name ?? $chairPerson->email }}](mailto:{{ $chairPerson->email }})

{{ __('mail.confirmRegistration5') }}

{{ __('mail.confirmRegistration6') }} 👋
{{ __(config('app.name')) }}
@endcomponent
