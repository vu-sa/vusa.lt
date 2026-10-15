<x-mail::message>
# {{ __('mail.member_inform.heading') }}

{{ __('mail.greeting_generic') }}

{{ __('mail.member_inform.intro', ['name' => $name, 'institution' => $institution->name]) }}

[{{ __('mail.member_inform.view_button') }}]({{ route('forms.show', $form_id) }})

{{ __('mail.member_inform.registrant_notified') }}

{{ __('mail.member_inform.goodbye') }}
</x-mail::message>
