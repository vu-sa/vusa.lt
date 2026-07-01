@component('mail::message')
# {{ __('mail.student_rep.confirm.heading') }} ✅

{{ __('mail.greeting', ['name' => $name ]) }} 👋

{{ __('mail.student_rep.confirm.intro') }}

## {{ __('mail.student_rep.confirm.subheading') }}

- {{ __('mail.student_rep.confirm.institution') }}: {{ $institution->name }}

@if($hasValidContactEmail)
{{ __('mail.student_rep.confirm.more_info1') }} [{{ $contactName }}](mailto:{{ $contactEmail }}), {{ __('mail.student_rep.confirm.more_info2') }}
@else
{{ __('mail.student_rep.confirm.will_contact') }}
@endif

{{ __('mail.student_rep.confirm.goodbye') }}
@endcomponent
