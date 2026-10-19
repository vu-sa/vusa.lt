<?php

namespace App\Actions\ActivityRequests;

use App\Enums\ActivityRequestSkipReason;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\NotificationType;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Services\MeetingCompletionService;
use App\Support\ActivityRequests\ActivityRequestPlanEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendInstitutionActivityRequests
{
    public function __construct(
        private readonly ResolveActivityRequestRecipients $recipients,
        private readonly MeetingCompletionService $completion,
        private readonly ResolveCheckInStarts $checkInStarts,
        private readonly ResolveActivityRequestEligibility $eligibility,
    ) {}

    /**
     * Without $pairs each institution asks its term's secretaries, or its representatives when none
     * are named. A coordinator who picks people ("institution_id:user_id") asks exactly them.
     *
     * @param  iterable<Institution>  $institutions
     * @param  list<string>|null  $pairs
     * @return list<ActivityRequestPlanEntry>
     */
    public function plan(iterable $institutions, ?User $requestedBy, InstitutionActivityCampaign $campaign = InstitutionActivityCampaign::ActivityConfirmation, ?array $pairs = null): array
    {
        $institutions = new EloquentCollection(collect($institutions)->all());
        $institutions->loadMissing(['meetings', 'checkIns', 'types']);
        if ($campaign === InstitutionActivityCampaign::MissingMeetings) {
            $institutions->loadMissing(['meetings.agendaItems.votes', 'meetings.institutions']);
        }
        $ids = $institutions->pluck('id');
        $candidates = $this->recipients->forInstitutions($ids);
        $typedInstitutionIds = Institution::query()->whereKey($ids)
            ->whereHas('duties.types', fn ($query) => $query->where('slug', 'studentu-atstovai'))
            ->pluck('id')->all();
        $existing = InstitutionActivityRequest::query()->whereIn('institution_id', $ids)->get()->groupBy('institution_id');
        $end = CarbonImmutable::today();

        return $institutions->map(function (Institution $institution) use ($requestedBy, $campaign, $pairs, $candidates, $typedInstitutionIds, $existing, $end): ActivityRequestPlanEntry {
            if ($requestedBy !== null && ! $requestedBy->can('askAboutActivity', [InstitutionCheckIn::class, $institution])) {
                return new ActivityRequestPlanEntry($institution, $campaign, $end, $end, skipReason: ActivityRequestSkipReason::NotAllowed);
            }
            if (! $institution->is_active) {
                return new ActivityRequestPlanEntry($institution, $campaign, $end, $end, skipReason: ActivityRequestSkipReason::InactiveInstitution);
            }

            $periods = $this->periodsFor($institution, $candidates[$institution->id], $pairs);
            if ($campaign === InstitutionActivityCampaign::ActivityConfirmation) {
                $periods = array_map(fn (array $period): array => [...$period, 'start' => $this->sinceLastActivity($institution, $period['start'])], $periods);
            }
            $recipients = collect();
            $recipientPeriods = [];
            $excluded = [];
            $firstSkip = null;

            foreach ($periods as $recipientId => ['user' => $recipient, 'start' => $start]) {
                $knownMeetings = $this->meetingsInPeriod($institution, $start, $end);
                $incompleteMeetings = $campaign === InstitutionActivityCampaign::MissingMeetings
                    ? $this->completion->incompleteInPeriod($knownMeetings, $start, $end)
                    : collect();
                $skip = $this->skipReasonFor($institution, (string) $recipientId, $campaign, $start, $end, $knownMeetings, $incompleteMeetings, $existing->get($institution->id, collect()), $requestedBy !== null);
                $row = $this->recipientRow($recipient, $start, $end, $skip, $incompleteMeetings);

                if ($skip !== null) {
                    $firstSkip ??= $skip;
                    $excluded[] = $row;

                    continue;
                }
                $recipients->push($recipient);
                $recipientPeriods[$recipientId] = $row;
            }

            return new ActivityRequestPlanEntry(
                institution: $institution,
                campaign: $campaign,
                periodStart: array_first($periods)['start'] ?? $end,
                periodEnd: $end,
                recipients: $recipients,
                recipientPeriods: $recipientPeriods,
                excludedRecipients: $excluded,
                skipReason: $recipients->isEmpty() ? ($firstSkip ?? (
                    in_array($institution->id, $typedInstitutionIds, true)
                        ? ActivityRequestSkipReason::NoRecipients
                        : ActivityRequestSkipReason::NoRepresentativeDuties
                )) : null,
            );
        })->values()->all();
    }

    /**
     * @param  iterable<Institution>  $institutions
     * @param  list<string>|null  $pairs
     * @return array{plan: list<ActivityRequestPlanEntry>, queued_recipients: int, queued_requests: int, queued_emails: int}
     */
    public function send(iterable $institutions, User $requestedBy, ?string $note = null, InstitutionActivityCampaign $campaign = InstitutionActivityCampaign::ActivityConfirmation, ?array $pairs = null): array
    {
        return DB::transaction(function () use ($institutions, $requestedBy, $note, $campaign, $pairs): array {
            ['plan' => $plan, 'byRecipient' => $byRecipient] = $this->queue($this->lock(collect($institutions)), $requestedBy, $note, $campaign, (string) Str::ulid(), pairs: $pairs);
            $this->notify($byRecipient);

            return ['plan' => $plan, ...$this->queuedSummary($byRecipient)];
        });
    }

    /**
     * One send and one email per recipient for the whole run, as a manual send. Completing the records
     * also confirms the time after the last meeting, so a task asks for them first and about activity
     * only when no records are missing.
     *
     * @param  iterable<array{task: Task, institution: Institution, recipients: Collection<int, User>}>  $asks
     */
    public function forTasks(iterable $asks): void
    {
        $asks = collect($asks)->filter(fn (array $ask) => $ask['recipients']->isNotEmpty())->keyBy(fn (array $ask) => $ask['institution']->id);
        if ($asks->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($asks): void {
            $locked = $this->lock($asks->pluck('institution'));
            $sendId = (string) Str::ulid();
            $records = $this->queue($locked, null, null, InstitutionActivityCampaign::MissingMeetings, $sendId, $asks)['byRecipient'];

            $askedForRecords = InstitutionActivityRequest::query()->whereIn('task_id', $asks->pluck('task.id'))
                ->where('campaign_type', InstitutionActivityCampaign::MissingMeetings)->pluck('task_id');
            $remaining = $asks->reject(fn (array $ask) => $askedForRecords->contains($ask['task']->id));
            $activity = $remaining->isEmpty() ? [] : $this->queue(
                $locked->whereIn('id', $remaining->keys()), null, null, InstitutionActivityCampaign::ActivityConfirmation, $sendId, $remaining,
            )['byRecipient'];

            foreach ($activity as $recipientId => ['user' => $recipient, 'requests' => $requests]) {
                $records[$recipientId]['user'] = $recipient;
                $records[$recipientId]['requests'] = [...$records[$recipientId]['requests'] ?? [], ...$requests];
            }
            $this->notify($records);
        });
    }

    /**
     * @param  array{secretaries: array<string, array{user: User, start: CarbonImmutable}>, representatives: array<string, array{user: User, start: CarbonImmutable}>}  $candidates
     * @param  list<string>|null  $pairs
     * @return array<string, array{user: User, start: CarbonImmutable}>
     */
    private function periodsFor(Institution $institution, array $candidates, ?array $pairs): array
    {
        ['secretaries' => $secretaries, 'representatives' => $representatives] = $candidates;

        if ($pairs === null) {
            return $secretaries ?: $representatives;
        }

        return array_filter(
            $secretaries + $representatives,
            fn ($userId) => in_array($institution->id.':'.$userId, $pairs, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Ask from the latest of the tenure start, the day after the last meeting and the day after the
     * last report, as the check-in form does; a question about an already covered time only repeats it.
     */
    private function sinceLastActivity(Institution $institution, CarbonImmutable $tenureStart): CarbonImmutable
    {
        return CarbonImmutable::parse($this->checkInStarts->startFor($institution, $tenureStart)['from']);
    }

    /** @return EloquentCollection<int, Meeting> */
    private function meetingsInPeriod(Institution $institution, CarbonImmutable $start, CarbonImmutable $end): EloquentCollection
    {
        return $institution->meetings->filter(function (Meeting $meeting) use ($start, $end): bool {
            $date = $meeting->start_time->toDateString();

            return $date >= $start->toDateString() && $date <= $end->toDateString();
        });
    }

    /**
     * @param  EloquentCollection<int, Meeting>  $knownMeetings
     * @param  EloquentCollection<int, Meeting>|Collection<int, Meeting>  $incompleteMeetings
     * @param  Collection<int, InstitutionActivityRequest>  $existing
     */
    private function skipReasonFor(
        Institution $institution,
        string $recipientId,
        InstitutionActivityCampaign $campaign,
        CarbonImmutable $start,
        CarbonImmutable $end,
        EloquentCollection $knownMeetings,
        Collection $incompleteMeetings,
        Collection $existing,
        bool $manual,
    ): ?ActivityRequestSkipReason {
        $missingMeetings = $campaign === InstitutionActivityCampaign::MissingMeetings;

        $eligibilitySkip = $missingMeetings ? null : $this->eligibility->skipReason($institution, $start, manual: $manual);

        return match (true) {
            $eligibilitySkip !== null => $eligibilitySkip,
            $start->gt($end) => ActivityRequestSkipReason::NoPeriod,
            $missingMeetings && $knownMeetings->isEmpty() => ActivityRequestSkipReason::NoRecordedMeetings,
            $missingMeetings && $incompleteMeetings->isEmpty() => ActivityRequestSkipReason::RecordsComplete,
            $existing->contains(fn (InstitutionActivityRequest $request) => $request->alreadyAsks($recipientId, $campaign, $start, $end)) => ActivityRequestSkipReason::AlreadyAsked,
            default => null,
        };
    }

    /**
     * @param  EloquentCollection<int, Meeting>|Collection<int, Meeting>  $incompleteMeetings
     * @return array{id: string, name: string, period_start: string, period_end: string, delivery_mode: string, skip_reason: string|null, incomplete_meetings: list<array{id: string, date: string, status: string}>}
     */
    private function recipientRow(User $recipient, CarbonImmutable $start, CarbonImmutable $end, ?ActivityRequestSkipReason $skip, Collection $incompleteMeetings): array
    {
        return [
            'id' => $recipient->id,
            'name' => $recipient->name,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'delivery_mode' => $recipient->isGloballyMuted() ? 'muted' : $recipient->emailDeliveryFor(NotificationType::InstitutionActivity)->value,
            'skip_reason' => $skip?->value,
            'incomplete_meetings' => $incompleteMeetings->map(fn (Meeting $meeting) => [
                'id' => $meeting->id,
                'date' => $meeting->start_time->toDateString(),
                'status' => $this->completion->calculate($meeting),
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     * @return EloquentCollection<int, Institution>
     */
    private function lock(Collection $institutions): EloquentCollection
    {
        return Institution::query()->whereIn('id', $institutions->pluck('id'))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * A task asks only its own assignees, and each of them once.
     *
     * @param  EloquentCollection<int, Institution>  $institutions
     * @param  Collection<string, array{task: Task, institution: Institution, recipients: Collection<int, User>}>|null  $asks  keyed by institution id
     * @param  list<string>|null  $pairs
     * @return array{plan: list<ActivityRequestPlanEntry>, byRecipient: array<string, array{user: User, requests: list<InstitutionActivityRequest>}>}
     */
    private function queue(EloquentCollection $institutions, ?User $requestedBy, ?string $note, InstitutionActivityCampaign $campaign, string $sendId, ?Collection $asks = null, ?array $pairs = null): array
    {
        $plan = $this->plan($institutions, $requestedBy, $campaign, $pairs);
        $alreadyAskedForTask = $asks === null ? collect() : InstitutionActivityRequest::query()
            ->whereIn('task_id', $asks->pluck('task.id'))->where('campaign_type', $campaign)
            ->get(['task_id', 'recipient_id'])->map(fn (InstitutionActivityRequest $request) => $request->task_id.':'.$request->recipient_id);
        $byRecipient = [];

        foreach ($plan as $entry) {
            $ask = $asks?->get($entry->institution->id);
            if ($ask !== null && $campaign === InstitutionActivityCampaign::MissingMeetings
                && $this->eligibility->skipReason($entry->institution, $entry->periodStart) !== null) {
                continue;
            }
            foreach ($entry->recipients as $recipient) {
                if ($ask !== null && (! $ask['recipients']->contains('id', $recipient->id) || $alreadyAskedForTask->contains($ask['task']->id.':'.$recipient->id))) {
                    continue;
                }
                $period = $entry->recipientPeriods[$recipient->id];
                $byRecipient[$recipient->id]['user'] = $recipient;
                $byRecipient[$recipient->id]['requests'][] = InstitutionActivityRequest::create([
                    'send_id' => $sendId, 'institution_id' => $entry->institution->id, 'recipient_id' => $recipient->id,
                    'requested_by_id' => $requestedBy?->id, 'task_id' => $ask === null ? null : $ask['task']->id, 'campaign_type' => $campaign,
                    'period_start' => $period['period_start'], 'period_end' => $period['period_end'], 'locale' => app()->getLocale(),
                    'note' => $note, 'expires_at' => now()->addDays(InstitutionActivityRequest::LINK_LIFETIME_DAYS),
                ]);
            }
        }

        return ['plan' => $plan, 'byRecipient' => $byRecipient];
    }

    /**
     * @param  array<string, array{user: User, requests: list<InstitutionActivityRequest>}>  $byRecipient
     */
    private function notify(array $byRecipient): void
    {
        foreach ($byRecipient as ['user' => $recipient, 'requests' => $requests]) {
            $recipient->notify(new InstitutionActivityNotification(new EloquentCollection($requests))->afterCommit());
        }
    }

    /**
     * @param  array<string, array{user: User, requests: list<InstitutionActivityRequest>}>  $byRecipient
     * @return array{queued_recipients: int, queued_requests: int, queued_emails: int}
     */
    private function queuedSummary(array $byRecipient): array
    {
        $byRecipient = collect($byRecipient);

        return [
            'queued_recipients' => $byRecipient->count(),
            'queued_requests' => $byRecipient->sum(fn (array $entry) => count($entry['requests'])),
            'queued_emails' => $byRecipient->filter(fn (array $entry) => ! $entry['user']->isGloballyMuted()
                && $entry['user']->emailDeliveryFor(NotificationType::InstitutionActivity)->value === 'immediate')->count(),
        ];
    }
}
