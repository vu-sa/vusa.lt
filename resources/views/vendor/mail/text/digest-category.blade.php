@props(['label', 'color' => '', 'items' => [], 'remaining' => 0, 'moreUrl' => '#'])
## {{ $label }}

@foreach ($items as $item)
- {{ $item['title'] }}: {{ $item['body'] }}
@if (! empty($item['context']))
@foreach ($item['context'] as $row)
  {{ $row['label'] }}: {{ $row['value'] }}
@endforeach
@endif
  {{ $item['primaryAction']['url'] ?? $item['url'] }}
@endforeach
@if ($remaining > 0)
+ {{ $remaining }} {{ trans_choice('notifications.digest_more_items', $remaining) }}: {{ $moreUrl }}
@endif
