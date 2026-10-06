@props(['question'])
<p><strong>{{ $question['institution'] }}</strong> · {{ $question['since'] }} – {{ $question['until'] }}</p>
@foreach ($question['meetings'] as $meeting)
<p><a href="{{ $meeting['url'] }}">{{ $meeting['date'] }} · {{ $meeting['label'] }}</a></p>
@endforeach
<x-mail::activity-actions :question="$question" />
<p><a href="{{ $question['notMine'] }}">{{ __('notifications.action_not_mine') }}</a></p>
