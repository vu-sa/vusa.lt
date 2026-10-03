@php
    use App\Enums\InstitutionActivityAnswer;

    $institution = $activityRequest->institution;
    $since = $activityRequest->period_start->toDateString();
    $missing = $activityRequest->campaign_type === \App\Enums\InstitutionActivityCampaign::MissingMeetings;
    $isOpen = $activityRequest->isOpen();
    $inputClass = 'mt-1 block w-full h-11 border border-border bg-background px-3 text-foreground';
    $buttonClass = 'inline-flex h-11 items-center justify-center px-4 text-xs font-bold uppercase tracking-wide';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ 'VU SA · '.__('activity_requests.campaigns.'.$activityRequest->campaign_type->value).' · '.$institution->name }}</title>
        @vite(['resources/css/app.css', 'resources/js/activity-reply.ts'])
    </head>
    <body data-surface="public" class="min-h-screen bg-background text-foreground antialiased">
        <main class="mx-auto max-w-xl px-5 py-10 sm:py-16">
            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Mano VU SA</p>
            <h1 class="mt-2 text-2xl font-bold">{{ 'VU SA · '.__('activity_requests.campaigns.'.$activityRequest->campaign_type->value).' · '.$institution->name }}</h1>

            @if (! $isOpen)
                <div class="mt-6 border-l-2 border-brand pl-4">
                    <p class="text-lg">
                        @if ($activityRequest->answer !== null)
                            {{ __('activity_requests.done.'.$activityRequest->answer->value) }}
                        @else
                            {{ __('activity_requests.done.resolved', ['institution' => $institution->name]) }}
                        @endif
                    </p>
                    @if ($activityRequest->meeting !== null)
                        <a class="mt-3 inline-block text-brand underline-offset-4 hover:underline" href="{{ route('meetings.show', $activityRequest->meeting) }}">
                            {{ __('activity_requests.add_agenda') }}
                        </a>
                    @endif
                </div>
            @else
                <p class="mt-3 text-muted-foreground">{{ __('activity_requests.fixed_period', ['start' => $since, 'end' => $activityRequest->periodEnd()->toDateString()]) }}</p>

                @if ($missing)
                    <section class="mt-6 border-y border-border py-4">
                        <h2 class="text-sm font-bold">{{ __('activity_requests.incomplete_meetings') }}</h2>
                        <p class="mt-2 text-sm text-muted-foreground">{{ __('activity_requests.edit_records_hint') }}</p>
                        @forelse ($incompleteMeetings as $meeting)
                            <a class="mt-2 flex min-h-11 items-center border border-border px-3 text-sm text-brand underline" href="{{ route('meetings.show', $meeting) }}">
                                {{ $meeting->start_time->toDateString() }} · {{ $meeting->completion_status === 'no_items' ? __('activity_requests.meeting_status.no_items') : __('activity_requests.meeting_status.incomplete') }}
                            </a>
                        @empty
                            <p class="mt-2 text-sm">{{ __('activity_requests.records_completed') }}</p>
                        @endforelse
                    </section>
                @endif

                @if ($activityRequest->note !== null && $activityRequest->requestedBy !== null)
                    <blockquote class="mt-4 border-l-2 border-border pl-4">
                        <p class="text-sm text-muted-foreground">{{ __('activity_requests.asked_by', ['name' => $activityRequest->requestedBy->name]) }}</p>
                        <p class="mt-1">{{ $activityRequest->note }}</p>
                    </blockquote>
                @endif

                @if ($errors->any())
                    <ul class="mt-6 border border-destructive/40 p-3 text-sm text-destructive">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                @php
                    $choices = [
                        ['value' => 'met', 'label' => $missing ? __('activity_requests.add_missing_meeting') : __('notifications.action_register_meeting'), 'hint' => '', 'submit' => __('activity_requests.record_meeting')],
                        ['value' => $missing ? 'complete' : 'not_met', 'label' => $missing ? __('activity_requests.complete') : __('notifications.action_report_activity'),
                         'hint' => $missing ? __('activity_requests.complete_hint') : __('activity_requests.not_met_hint', ['date' => $since, 'institution' => $institution->name]),
                         'submit' => $missing ? __('activity_requests.complete') : __('activity_requests.confirm_not_met')],
                        ['value' => 'not_mine', 'label' => __('notifications.action_not_mine'), 'hint' => __('activity_requests.not_mine_hint'), 'submit' => __('activity_requests.confirm_not_mine')],
                    ];
                    $replyProps = [
                        'action' => $activityRequest->submitUrl(), 'csrf' => csrf_token(), 'start' => $since, 'end' => $activityRequest->periodEnd()->toDateString(),
                        'locale' => app()->getLocale(), 'chosen' => old('answer', $chosen?->value), 'choices' => $choices,
                        'types' => \App\Enums\MeetingType::toArray(app()->getLocale()),
                        'text' => collect(['met_hint', 'known_meetings', 'meeting_date', 'meeting_time', 'meeting_type', 'remove_meeting', 'date_hint', 'meeting_time_hint', 'add_meeting'])
                            ->mapWithKeys(fn ($key) => [$key => __('activity_requests.'.$key)])->all(),
                        'errors' => collect($errors->messages())->map(fn ($messages) => $messages[0])->all(),
                        'knownDates' => $missing ? $knownMeetings->map(fn ($meeting) => $meeting->start_time->toDateString())->all() : [],
                        'oldMeetings' => old('meetings', []), 'translations' => ['Pasirinkti datą' => app()->getLocale() === 'en' ? 'Choose a date' : 'Pasirinkti datą', 'Išvalyti' => app()->getLocale() === 'en' ? 'Clear' : 'Išvalyti'],
                    ];
                @endphp
                <script type="application/json" id="activity-reply-config">@json($replyProps)</script>
                <div id="activity-reply">
                    @foreach ($choices as $choice)
                        <details class="mt-3 border border-border p-4" @if (old('answer', $chosen?->value) === $choice['value']) open @endif>
                            <summary class="min-h-11 cursor-pointer font-bold focus-visible:outline-2 focus-visible:outline-ring">{{ $choice['label'] }}</summary>
                            <form method="POST" action="{{ $activityRequest->submitUrl() }}" class="mt-3 space-y-3">
                                @csrf
                                <input type="hidden" name="answer" value="{{ $choice['value'] }}">
                                @if ($choice['value'] === 'met')
                                    @foreach (old('meetings', [['date' => '', 'type' => 'in-person', 'time' => '']]) as $index => $row)
                                        <label class="block text-sm">{{ __('activity_requests.meeting_date') }}
                                            <input type="date" name="meetings[{{ $index }}][date]" value="{{ $row['date'] ?? '' }}" min="{{ $since }}" max="{{ $activityRequest->periodEnd()->toDateString() }}" required class="{{ $inputClass }}">
                                        </label>
                                        <label class="block text-sm">{{ __('activity_requests.meeting_type') }}
                                            <select name="meetings[{{ $index }}][type]" class="{{ $inputClass }}">
                                                @foreach ($meetingTypes as $type)
                                                    <option value="{{ $type->value }}" @selected(($row['type'] ?? 'in-person') === $type->value)>{{ $type->label(app()->getLocale()) }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="block text-sm">{{ __('activity_requests.meeting_time') }}
                                            <input type="time" name="meetings[{{ $index }}][time]" value="{{ $row['time'] ?? '' }}" class="{{ $inputClass }}">
                                        </label>
                                    @endforeach
                                    <p class="text-xs text-muted-foreground">{{ __('activity_requests.meeting_time_hint') }}</p>
                                @else
                                    <p>{{ $choice['hint'] }}</p>
                                @endif
                                <button type="submit" class="{{ $buttonClass }} bg-brand-fill text-brand-foreground">{{ $choice['submit'] }}</button>
                            </form>
                        </details>
                    @endforeach
                </div>

                <p class="mt-4 text-xs text-muted-foreground">{{ __('activity_requests.answering_as', ['name' => $activityRequest->recipient->name]) }}</p>
            @endif

            @if ($others->isNotEmpty())
                <h2 class="mt-10 text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ __('activity_requests.others_title') }}</h2>
                <ul class="mt-2 divide-y divide-border border-y border-border">
                    @foreach ($others as $other)
                        <li>
                            <a class="flex min-h-11 items-center justify-between gap-3 py-2 hover:text-brand" href="{{ $other->answerUrl() }}">
                                <span>{{ $other->institution->name }}</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <a class="mt-10 inline-block text-sm text-muted-foreground underline-offset-4 hover:underline" href="{{ route('dashboard') }}">{{ __('activity_requests.open_mano') }}</a>
        </main>
    </body>
</html>
