@props(['label', 'color' => '#58554f', 'items' => [], 'remaining' => 0, 'moreUrl' => '#'])
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 28px 0 0;">
<tr>
<td style="border-bottom: 1px solid #e4e4e7; padding-bottom: 8px;">
<span style="display: inline-block; width: 8px; height: 8px; background-color: {{ $color }}; margin-right: 8px;"></span>
<span style="font-size: 12px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: #52525b;">{{ $label }}</span>
</td>
</tr>
@foreach ($items as $item)
<tr>
<td style="border-bottom: 1px solid #e4e4e7; padding: 14px 0;">
<a href="{{ $item['url'] }}" style="font-size: 15px; font-weight: 600; color: #27272a; text-decoration: none;">{{ Str::limit($item['title'], 80) }}</a>
<div style="font-size: 14px; color: #52525b; margin-top: 2px;">{{ Str::limit($item['body'], 120) }}</div>
@if (! empty($item['context']))
<div style="font-size: 13px; color: #71717a; margin-top: 4px;">
@foreach ($item['context'] as $row)
{{ $row['label'] }}: {{ $row['value'] }}@if (! $loop->last) · @endif
@endforeach
</div>
@endif
@if (! empty($item['primaryAction']))
<div style="margin-top: 6px;"><a href="{{ $item['primaryAction']['url'] }}" style="font-size: 13px; font-weight: 600; color: #bd2835; text-decoration: none;">{{ $item['primaryAction']['label'] }} →</a></div>
@endif
</td>
</tr>
@endforeach
@if ($remaining > 0)
<tr>
<td style="padding: 12px 0;">
<a href="{{ $moreUrl }}" style="font-size: 13px; color: #71717a;">+ {{ $remaining }} {{ trans_choice('notifications.digest_more_items', $remaining) }} →</a>
</td>
</tr>
@endif
</table>
