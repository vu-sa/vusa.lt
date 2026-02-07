@component('mail::message')
# {{ __('mail.student_rep.inform.heading') }}

{{ __('mail.student_rep.inform.intro', ['name' => $name, 'institution' => $institution->name]) }}

@component('mail::button', ['url' => route('forms.show', $form_id)])
{{ __('mail.student_rep.inform.view_button') }}
@endcomponent

{{ __('mail.student_rep.inform.registrant_notified') }}

{{ __('mail.student_rep.inform.goodbye') }}
@endcomponent
