@component('mail::message')
# {{ __('mail.confirm.heading') }} ✅

{{ __('mail.greeting', ['name' => $name ]) }} 👋

{{ __('mail.confirm.intro') }}

## {{ __('mail.confirm.subheading') }}

- {{ __('mail.confirm.institution') }}: {{ $institution->name }}

{{ __('mail.confirm.more_info1') }} [{{ $contactName }}](mailto:{{ $dutyContact->email }}), {{ __('mail.confirm.more_info2') }}

{{ __('mail.confirm.goodbye') }}
@endcomponent
