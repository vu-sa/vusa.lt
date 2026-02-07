<x-mail::message>
{{-- Hero Header with Count Badge --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px;">
<tr>
<td>
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="background-color: #bd2835; color: #ffffff; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
{{ $totalCount }} {{ trans_choice('notifications.digest_count_label', $totalCount) }}
</td>
</tr>
</table>
</td>
</tr>
</table>

<h1 style="margin-top: 0; margin-bottom: 8px; font-size: 22px; font-weight: 700; color: #1a1a1a;">{{ __('notifications.digest_greeting', ['name' => $user->name]) }}</h1>

<p style="margin-top: 0; margin-bottom: 32px; color: #6b7280; font-size: 15px;">{{ __('notifications.digest_intro_short') }}</p>

@foreach ($categorizedItems as $categoryLabel => $items)
@php
    $color = $categoryColors[$categoryLabel] ?? 'gray';
    $borderColor = match($color) {
        'blue' => '#3b82f6',
        'orange' => '#f97316',
        'purple' => '#8b5cf6',
        'green' => '#22c55e',
        'cyan' => '#06b6d4',
        'amber' => '#f59e0b',
        'red' => '#ef4444',
        default => '#6b7280',
    };
    $bgColor = match($color) {
        'blue' => '#eff6ff',
        'orange' => '#fff7ed',
        'purple' => '#f5f3ff',
        'green' => '#f0fdf4',
        'cyan' => '#ecfeff',
        'amber' => '#fffbeb',
        'red' => '#fef2f2',
        default => '#f9fafb',
    };
@endphp

{{-- Category Section --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px;">
<tr>
<td style="border-left: 4px solid {{ $borderColor }}; padding-left: 16px;">
{{-- Category Header --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 12px;">
<tr>
<td>
<span style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $borderColor }};">{{ $categoryLabel }}</span>
@if(($remainingCounts[$categoryLabel] ?? 0) > 0)
<span style="font-size: 12px; color: #9ca3af; margin-left: 8px;">{{ count($items) + $remainingCounts[$categoryLabel] }} {{ __('notifications.digest_total') }}</span>
@endif
</td>
</tr>
</table>

{{-- Items Container --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: {{ $bgColor }}; border-radius: 8px;">
@foreach ($items as $index => $item)
<tr>
<td style="padding: 14px 16px; {{ $index < count($items) - 1 ? 'border-bottom: 1px solid rgba(0,0,0,0.06);' : '' }}">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="vertical-align: top; width: 28px; padding-right: 12px;">
<span style="font-size: 18px;">{{ $item['icon'] }}</span>
</td>
<td style="vertical-align: top;">
<a href="{{ $item['url'] }}" style="font-size: 14px; font-weight: 600; color: #1a1a1a; text-decoration: none; display: block; margin-bottom: 4px;">{{ Str::limit($item['title'], 60) }}</a>
<span style="font-size: 13px; color: #6b7280; line-height: 1.4;">{{ Str::limit($item['body'], 100) }}</span>
</td>
<td style="vertical-align: middle; width: 32px; text-align: right;">
<a href="{{ $item['url'] }}" style="color: {{ $borderColor }}; text-decoration: none; font-size: 18px;">→</a>
</td>
</tr>
</table>
</td>
</tr>
@endforeach

{{-- More Items Link --}}
@if(($remainingCounts[$categoryLabel] ?? 0) > 0)
<tr>
<td style="padding: 12px 16px; border-top: 1px dashed rgba(0,0,0,0.1); text-align: center;">
<a href="{{ $dashboardUrl }}" style="font-size: 13px; color: {{ $borderColor }}; text-decoration: none; font-weight: 600;">
+ {{ $remainingCounts[$categoryLabel] }} {{ trans_choice('notifications.digest_more_items', $remainingCounts[$categoryLabel]) }} →
</a>
</td>
</tr>
@endif
</table>

</td>
</tr>
</table>
@endforeach

{{-- Primary CTA --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 32px; margin-bottom: 24px;">
<tr>
<td align="center">
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="background-color: #1a1a1a; border-radius: 6px;">
<a href="{{ $dashboardUrl }}" style="display: inline-block; padding: 14px 28px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none;">
{{ __('notifications.digest_view_all') }}
</a>
</td>
</tr>
</table>
</td>
</tr>
</table>

{{-- Footer Note --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 16px;">
<tr>
<td style="text-align: center;">
<p style="font-size: 12px; color: #9ca3af; margin: 0;">{{ __('notifications.digest_footer') }}</p>
</td>
</tr>
</table>

</x-mail::message>
