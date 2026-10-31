@props(['label', 'color' => '', 'items' => [], 'remaining' => 0, 'moreUrl' => '#'])
## {{ $label }}

@foreach ($items as $section)
@if ($section['count'] > 1)
{{ $section['title'] }} ({{ $section['count'] }})
@foreach ($section['items'] as $item)
- {{ $item['digestLine'] ?? $item['body'] }}: {{ $item['url'] }}
@endforeach
@else
@foreach ($section['items'] as $item)
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
@endif
@endforeach
@if ($remaining > 0)
+ {{ $remaining }} {{ trans_choice('notifications.digest_more_items', $remaining) }}: {{ $moreUrl }}
@endif
