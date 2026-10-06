<?php

namespace App\Notifications;

use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\NotificationType;
use App\Models\InstitutionActivityRequest;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * "Ar vyko posėdis?" for one recipient: every institution from one send, each answerable from
 * the email without signing in (U21). Links open a confirmation page; nothing is recorded on GET.
 */
class InstitutionActivityNotification extends BaseNotification
{
    /**
     * @param  Collection<int, InstitutionActivityRequest>  $requests
     */
    public function __construct(private Collection $requests)
    {
        $locale = $requests->first()?->locale;
        $this->locale($locale ?? app()->getLocale());
    }

    public function type(): NotificationType
    {
        return NotificationType::InstitutionActivity;
    }

    public function title(object $notifiable): string
    {
        return 'VU SA · '.__($this->first()->campaign_type->labelKey());
    }

    public function body(object $notifiable): string
    {
        $request = $this->first();
        $note = $request->note !== null && $request->requestedBy !== null
            ? ' '.__('notifications.activity_request_note', ['name' => $request->requestedBy->name, 'note' => $request->note]) : '';

        return __($request->campaign_type->bodyKey()).$note;
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $this->requests = InstitutionActivityRequest::query()->open()->whereKey($this->requests->modelKeys())
            ->whereHas('institution')->whereHas('recipient')->with(['institution.meetings', 'requestedBy', 'task'])->get();
        $this->requests->where('campaign_type', InstitutionActivityCampaign::MissingMeetings)
            ->loadMissing(['institution.meetings.agendaItems.votes', 'institution.meetings.institutions']);
        $this->requests = $this->requests->reject(fn (InstitutionActivityRequest $request) => $request->campaign_type === InstitutionActivityCampaign::MissingMeetings
                ? $request->incompleteMeetings()->isEmpty()
                : $request->institution->meetings->contains(fn ($meeting) => $meeting->start_time->toDateString() >= $request->period_start->toDateString()
                    && $meeting->start_time->toDateString() <= $request->periodEnd()->toDateString()));

        return $this->requests->isNotEmpty() && ($channel !== 'mail' || (! $notifiable->isGloballyMuted() && $notifiable->emailDeliveryFor($this->type())->value === 'immediate'));
    }

    public function url(): string
    {
        return $this->first()->answerUrl();
    }

    public function modelClass(): ?string
    {
        return 'Institution';
    }

    public function object(): ?array
    {
        if (! $this->isSingle()) {
            return null;
        }

        $institution = $this->first()->institution;

        return [
            'modelClass' => 'Institution',
            'name' => $institution->name,
            'url' => route('institutions.show', $institution),
            'id' => $institution->id,
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        $request = $this->first();
        $days = $request->task?->metadata['effective_days_since_activity'] ?? null;

        return $this->contextRows([
            'institution' => $this->isSingle() ? $request->institution->name : null,
            'since' => $this->isSingle() ? $request->period_start->toDateString().' – '.$request->periodEnd()->toDateString() : null,
            'days_since_activity' => $this->isSingle() && is_numeric($days) ? __('notifications.context.days_value', ['count' => (int) $days]) : null,
            'asked_by' => $request->requestedBy?->name,
        ]);
    }

    /**
     * One institution answers right here; several open a page that lists them all.
     */
    #[\Override]
    public function primaryAction(): ?array
    {
        if ($this->first()->campaign_type === InstitutionActivityCampaign::MissingMeetings) {
            return ['label' => __('activity_requests.edit_records'), 'url' => $this->url()];
        }
        if (! $this->isSingle()) {
            return ['label' => __('notifications.action_answer'), 'url' => $this->url()];
        }

        return [
            'label' => __('notifications.action_register_meeting'),
            'url' => $this->first()->answerUrl(InstitutionActivityAnswer::Met),
        ];
    }

    #[\Override]
    public function secondaryAction(): ?array
    {
        if (! $this->isSingle()) {
            return null;
        }

        return [
            'label' => $this->first()->campaign_type === InstitutionActivityCampaign::MissingMeetings ? __('activity_requests.complete') : __('notifications.action_report_activity'),
            'url' => $this->first()->answerUrl($this->first()->campaign_type === InstitutionActivityCampaign::MissingMeetings ? InstitutionActivityAnswer::Complete : InstitutionActivityAnswer::NotMet),
        ];
    }

    #[\Override]
    public function toMail(object $notifiable): MailMessage|Mailable
    {
        return (new MailMessage)
            ->subject(Str::limit($this->title($notifiable), 59, '…'))
            ->markdown('emails.institution-activity', [
                'title' => $this->title($notifiable),
                'body' => $this->body($notifiable),
                'context' => $this->context($notifiable),
                'questions' => $this->questions(),
                'isSingle' => $this->isSingle(),
                'category' => __($this->category()->labelKey()),
                'settingsUrl' => route('profile.notifications'),
            ]);
    }

    public function toDigestItem(object $notifiable): array
    {
        return [...parent::toDigestItem($notifiable), 'activity_request_ids' => $this->requests->modelKeys(), 'activity_questions' => $this->questions()];
    }

    private function questions(): array
    {
        $this->requests->where('campaign_type', InstitutionActivityCampaign::MissingMeetings)
            ->loadMissing(['institution.meetings.agendaItems.votes', 'institution.meetings.institutions']);

        return $this->requests->map(function (InstitutionActivityRequest $request): array {
            $missing = $request->campaign_type === InstitutionActivityCampaign::MissingMeetings;

            return [
                'institution' => $request->institution->name,
                'since' => $request->period_start->toDateString(), 'until' => $request->periodEnd()->toDateString(),
                'primaryLabel' => $missing ? __('activity_requests.edit_records') : __('notifications.action_register_meeting'),
                'secondaryLabel' => $missing ? __('activity_requests.complete') : __('notifications.action_report_activity'),
                'met' => $request->answerUrl($missing ? null : InstitutionActivityAnswer::Met),
                'notMet' => $request->answerUrl($missing ? InstitutionActivityAnswer::Complete : InstitutionActivityAnswer::NotMet),
                'notMine' => $request->answerUrl(InstitutionActivityAnswer::NotMine),
                'meetings' => $missing ? $request->incompleteMeetings()->map(fn ($meeting) => [
                    'date' => $meeting->start_time->toDateString(), 'url' => route('meetings.show', $meeting),
                    'label' => $meeting->completion_status === 'no_items' ? __('activity_requests.meeting_status.no_items') : __('activity_requests.meeting_status.incomplete'),
                ])->values()->all() : [],
            ];
        })->all();
    }

    private function isSingle(): bool
    {
        return $this->requests->count() === 1;
    }

    private function first(): InstitutionActivityRequest
    {
        /** @var InstitutionActivityRequest */
        return $this->requests->first();
    }
}
