@props(['public' => false])
@if($public)
{{ __('mail.public_description') }}

{{ __('mail.open_public_site') }}: {{ config('app.url') }}
@else
{{ __('mail.platform_description') }}

{{ __('mail.open_platform') }}: {{ route('dashboard') }}
@endif

{{ $slot }}
