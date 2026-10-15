@props(['question'])
{{ $question['institution'] }} · {{ $question['since'] }} – {{ $question['until'] }}
@foreach ($question['meetings'] as $meeting)
{{ $meeting['date'] }} · {{ $meeting['label'] }}: {{ $meeting['url'] }}
@endforeach
<x-mail::activity-actions :question="$question" />
{{ __('notifications.action_not_mine') }}: {{ $question['notMine'] }}
