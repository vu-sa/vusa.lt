@props(['public' => false])
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
@if($public)
<p>{{ __('mail.public_description') }}</p>
<p><a href="{{ config('app.url') }}">{{ __('mail.open_public_site') }}</a></p>
@else
<p>{{ __('mail.platform_description') }}</p>
<p><a href="{{ route('dashboard') }}">{{ __('mail.open_platform') }}</a></p>
@endif
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
