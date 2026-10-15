@props(['label', 'color' => '', 'items' => [], 'remaining' => 0, 'moreUrl' => '#'])
## {{ $label }}

@foreach ($items as $item)
- {{ $item['title'] }}: {{ $item['body'] }}
@if (! empty($item['context']))
@foreach ($item['context'] as $row)
  {{ $row['label'] }}: {{ $row['value'] }}
@endforeach
@endif
@if (! empty($item['activity_questions']))
@foreach ($item['activity_questions'] as $question)
<x-mail::activity-question :question="$question" />
@endforeach
@else
  {{ $item['primaryAction']['url'] ?? $item['url'] }}
@endif
@endforeach
@if ($remaining > 0)
+ {{ $remaining }} {{ trans_choice('notifications.digest_more_items', $remaining) }}: {{ $moreUrl }}
@endif
