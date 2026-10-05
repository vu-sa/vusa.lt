<?php

namespace App\Actions\ActivityRequests;

use App\Actions\Cadences\ResolveCadenceForInstitution;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\NotificationType;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionSecretary;
use App\Models\Pivots\Dutiable;
use App\Models\Task;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Services\MeetingCompletionService;
use App\Support\MorphMap;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendInstitutionActivityRequests
{
    public const string SKIP_NOT_ALLOWED = 'not_allowed';

    public const string SKIP_NO_RECIPIENTS = 'no_recipients';

    public const string SKIP_CHECK_IN_ACTIVE = 'check_in_active';

    public const string SKIP_RECENT_ACTIVITY = 'recent_activity';

    public const string SKIP_ALREADY_ASKED = 'already_asked';

    /**
     * Without $pairs each institution asks its term's secretaries, or its representatives when none
     * are named. A coordinator who picks people ("institution_id:user_id") asks exactly them.
     *
     * @param  iterable<Institution>  $institutions
     * @param  list<string>|null  $pairs
     */
    public function plan(iterable $institutions, ?User $requestedBy, InstitutionActivityCampaign $campaign = InstitutionActivityCampaign::ActivityConfirmation, ?array $pairs = null): array
    {
        $institutions = new EloquentCollection(collect($institutions)->all());
        $institutions->loadMissing(['meetings', 'checkIns']);
        if ($campaign === InstitutionActivityCampaign::MissingMeetings) {
            $institutions->loadMissing(['meetings.agendaItems.votes', 'meetings.institutions']);
        }
        $ids = $institutions->pluck('id');
        $candidates = $this->candidates($ids);
        $existing = InstitutionActivityRequest::query()->whereIn('institution_id', $ids)->get()->groupBy('institution_id');
        $end = CarbonImmutable::today();
        $plan = [];

        foreach ($institutions as $institution) {
            $entry = ['institution' => $institution, 'recipients' => collect(), 'recipient_periods' => [], 'excluded_recipients' => [],
                'period_start' => $end, 'period_end' => $end, 'campaign_type' => $campaign, 'skip_reason' => null];
            if ($requestedBy !== null && ! $requestedBy->can('askAboutActivity', [InstitutionCheckIn::class, $institution])) {
                $entry['skip_reason'] = self::SKIP_NOT_ALLOWED;
                $plan[] = $entry;

                continue;
            }
            ['secretaries' => $secretaries, 'representatives' => $representatives] = $candidates[$institution->id];
            $periods = $pairs === null
                ? ($secretaries ?: $representatives)
                : array_filter($secretaries + $representatives, fn ($userId) => in_array($institution->id.':'.$userId, $pairs, true), ARRAY_FILTER_USE_KEY);
            foreach ($periods as $recipientId => ['user' => $recipient, 'start' => $start]) {
                $knownMeetings = $institution->meetings->filter(fn ($meeting) => $meeting->start_time->toDateString() >= $start->toDateString() && $meeting->start_time->toDateString() <= $end->toDateString());
                $incompleteMeetings = $campaign === InstitutionActivityCampaign::MissingMeetings
                    ? app(MeetingCompletionService::class)->incompleteInPeriod($knownMeetings, $start, $end) : collect();
                $skip = match (true) {
                    $start->gt($end) => 'no_period',
                    $campaign === InstitutionActivityCampaign::ActivityConfirmation && $knownMeetings->isNotEmpty() => 'meeting_recorded',
                    $campaign === InstitutionActivityCampaign::MissingMeetings && $knownMeetings->isEmpty() => 'no_recorded_meetings',
                    $campaign === InstitutionActivityCampaign::MissingMeetings && $incompleteMeetings->isEmpty() => 'records_complete',
                    $institution->checkIns->contains(fn ($checkIn) => $checkIn->start_date->lte($start) && $checkIn->end_date->gte($end)) => self::SKIP_CHECK_IN_ACTIVE,
                    $existing->get($institution->id, collect())->contains(fn ($request) => $request->recipient_id === $recipientId && $request->campaign_type === $campaign && $request->answer?->value !== 'not_mine' && ($request->isOpen() || $request->answered_at !== null || $request->resolved_at !== null) && $request->period_start->lte($start) && $request->periodEnd()->gte($end)) => self::SKIP_ALREADY_ASKED,
                    default => null,
                };
                $data = ['id' => $recipientId, 'name' => $recipient->name, 'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
                    'delivery_mode' => $recipient->isGloballyMuted() ? 'muted' : $recipient->emailDeliveryFor(NotificationType::InstitutionActivity)->value, 'skip_reason' => $skip,
                    'incomplete_meetings' => $incompleteMeetings->map(fn ($meeting) => ['id' => $meeting->id, 'date' => $meeting->start_time->toDateString(), 'status' => app(MeetingCompletionService::class)->calculate($meeting)])->values()->all()];
                if ($skip !== null) {
                    $entry['excluded_recipients'][] = $data;

                    continue;
                }
                $entry['recipients']->push($recipient);
                $entry['recipient_periods'][$recipientId] = $data;
            }
            $entry['period_start'] = array_first($periods)['start'] ?? $end;
            if ($entry['recipients']->isEmpty()) {
                $entry['skip_reason'] = $entry['excluded_recipients'][0]['skip_reason'] ?? self::SKIP_NO_RECIPIENTS;
            }
            $plan[] = $entry;
        }

        return $plan;
    }

    /**
     * The people a coordinator can pick: every institution's named secretaries and current representatives.
     *
     * @param  iterable<Institution>  $institutions
     * @return list<array{id: string, name: string, institutions: list<array{id: string, name: string}>}>
     */
    public function people(iterable $institutions): array
    {
        $institutions = collect($institutions)->keyBy('id');
        $people = [];
        foreach ($this->candidates($institutions->keys()) as $institutionId => $roles) {
            $institution = $institutions[$institutionId];
            foreach ($roles['secretaries'] + $roles['representatives'] as $userId => ['user' => $user]) {
                $people[$userId] ??= ['id' => (string) $userId, 'name' => $user->name, 'institutions' => []];
                $people[$userId]['institutions'][] = ['id' => (string) $institution->id, 'name' => is_string($institution->name) ? $institution->name : ''];
            }
        }

        return collect($people)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    /**
     * @param  Collection<int, string>  $ids
     * @return array<string, array{secretaries: array<string, array{user: User, start: CarbonImmutable}>, representatives: array<string, array{user: User, start: CarbonImmutable}>}>
     */
    private function candidates(Collection $ids): array
    {
        $cadences = ResolveCadenceForInstitution::forInstitutions($ids);
        $secretaries = InstitutionSecretary::query()->whereIn('institution_id', $ids)->with('user')->get()->groupBy('institution_id');
        $assignments = Dutiable::query()->current()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereHas('duty', fn ($query) => $query->whereIn('institution_id', $ids)->whereHas('types', fn ($type) => $type->where('slug', 'studentu-atstovai')))
            ->with(['duty', 'user'])->get()->groupBy('duty.institution_id');
        $candidates = [];
        foreach ($ids as $id) {
            $cadence = $cadences[$id] ?? null;
            $candidates[$id] = ['secretaries' => [], 'representatives' => []];
            foreach ($secretaries->get($id, collect()) as $row) {
                if ($cadence !== null && $row->cadence_id === $cadence->id && $row->user !== null) {
                    $candidates[$id]['secretaries'][$row->user_id] = ['user' => $row->user, 'start' => CarbonImmutable::instance($cadence->start_date)];
                }
            }
            foreach ($assignments->get($id, collect()) as $row) {
                if ($row->user === null) {
                    continue;
                }
                $start = CarbonImmutable::instance($row->start_date);
                if ($cadence !== null) {
                    $start = $start->max($cadence->start_date);
                }
                $current = $candidates[$id]['representatives'][$row->dutiable_id] ?? null;
                if ($current === null || $start->lt($current['start'])) {
                    $candidates[$id]['representatives'][$row->dutiable_id] = ['user' => $row->user, 'start' => $start];
                }
            }
        }

        return $candidates;
    }

    /** @param iterable<Institution> $institutions */
    /**
     * @param  iterable<Institution>  $institutions
     * @param  list<string>|null  $pairs
     */
    public function send(iterable $institutions, User $requestedBy, ?string $note = null, InstitutionActivityCampaign $campaign = InstitutionActivityCampaign::ActivityConfirmation, ?array $pairs = null): array
    {
        return $this->dispatch(collect($institutions), $requestedBy, $note, $campaign, pairs: $pairs);
    }

    /** @param Collection<int, User> $recipients */
    public function forTask(Task $task, Institution $institution, Collection $recipients): void
    {
        if ($recipients->isEmpty()) {
            return;
        }
        foreach (InstitutionActivityCampaign::cases() as $campaign) {
            $this->dispatch(collect([$institution]), null, null, $campaign, $task, $recipients);
        }
    }

    /** @param list<string>|null $pairs */
    private function dispatch(Collection $institutions, ?User $requestedBy, ?string $note, InstitutionActivityCampaign $campaign, ?Task $task = null, ?Collection $taskRecipients = null, ?array $pairs = null): array
    {
        return DB::transaction(function () use ($institutions, $requestedBy, $note, $campaign, $task, $taskRecipients, $pairs): array {
            $locked = Institution::query()->whereIn('id', $institutions->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            $plan = $this->plan($locked, $requestedBy, $campaign, $pairs);
            $taskRequestRecipients = $task === null ? collect() : InstitutionActivityRequest::query()
                ->where('task_id', $task->id)->whereIn('institution_id', $locked->modelKeys())
                ->where('campaign_type', $campaign)->pluck('recipient_id');
            $sendId = (string) Str::ulid();
            $byRecipient = [];
            foreach ($plan as &$entry) {
                foreach ($entry['recipients'] as $recipient) {
                    $period = $entry['recipient_periods'][$recipient->id];
                    if ($task !== null) {
                        if (! $taskRecipients->contains('id', $recipient->id) || $taskRequestRecipients->contains($recipient->id)) {
                            continue;
                        }
                        $hasMeeting = $entry['institution']->meetings->contains(fn ($meeting) => $meeting->start_time->toDateString() >= $period['period_start'] && $meeting->start_time->toDateString() <= $period['period_end']);
                        if (($campaign === InstitutionActivityCampaign::MissingMeetings) !== $hasMeeting) {
                            continue;
                        }
                    }
                    $request = InstitutionActivityRequest::create([
                        'send_id' => $sendId, 'institution_id' => $entry['institution']->id, 'recipient_id' => $recipient->id,
                        'requested_by_id' => $requestedBy?->id, 'task_id' => $task?->id, 'campaign_type' => $campaign,
                        'period_start' => $period['period_start'], 'period_end' => $period['period_end'], 'locale' => app()->getLocale(),
                        'note' => $note, 'expires_at' => now()->addDays(InstitutionActivityRequest::LINK_LIFETIME_DAYS),
                    ]);
                    $byRecipient[$recipient->id]['user'] = $recipient;
                    $byRecipient[$recipient->id]['requests'][] = $request;
                }
            }
            unset($entry);
            foreach ($byRecipient as ['user' => $recipient, 'requests' => $requests]) {
                $recipient->notify(new InstitutionActivityNotification(new EloquentCollection($requests))->afterCommit());
            }

            return ['plan' => $plan, 'queued_recipients' => count($byRecipient), 'queued_requests' => collect($byRecipient)->sum(fn ($entry) => count($entry['requests'])),
                'queued_emails' => collect($byRecipient)->filter(fn ($entry) => ! $entry['user']->isGloballyMuted() && $entry['user']->emailDeliveryFor(NotificationType::InstitutionActivity)->value === 'immediate')->count()];
        });
    }
}
