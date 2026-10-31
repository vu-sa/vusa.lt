<?php

namespace App\Services\MailPreview;

use App\Enums\NotificationType;
use App\Mail\NotificationDigest;
use App\Notifications\BaseNotification;
use App\Notifications\ReservationStatusChangedNotification;
use Closure;

class MailPreviewCatalog
{
    public const array NOTIFICATION_TYPES = [
        NotificationType::TaskAssigned, NotificationType::TaskReminder, NotificationType::TaskOverdue,
        NotificationType::TaskAutoCompleted, NotificationType::MeetingCreated, NotificationType::MeetingAgendaCompleted,
        NotificationType::FollowedInstitutionActivity, NotificationType::MeetingReminder, NotificationType::InstitutionActivity,
        NotificationType::InstitutionActivityNotMine, NotificationType::ApprovalRequested, NotificationType::ReservationStatusChanged,
        NotificationType::AssignedToResource, NotificationType::ReservationDraftItemTaken, NotificationType::CommentActivity,
        NotificationType::CommentMention, NotificationType::DutyExpiring, NotificationType::AccessChanged,
        NotificationType::MemberRegistration, NotificationType::StudentRepRegistration, NotificationType::SupportRequestCreated,
        NotificationType::SupportRequestAssigned, NotificationType::SupportRequestInvolved,
        NotificationType::SupportRequestStatusChanged, NotificationType::Welcome,
    ];

    public function __construct(private MailPreviewSamples $samples, private MailPreviewRenderer $renderer) {}

    /** @return array<string, array{key: string, label: string, group: string, render: Closure}> */
    public function entries(): array
    {
        $entries = [];
        foreach (['single', 'grouped', 'overflow', 'overdue', 'legacy'] as $scenario) {
            $key = 'digest-'.$scenario;
            $entries[$key] = [
                'key' => $key, 'label' => 'mail_previews.scenarios.'.$scenario, 'group' => 'mail_previews.groups.digest',
                'render' => fn () => new NotificationDigest($this->samples->user(), $this->samples->digestItems($scenario)),
            ];
        }
        foreach (self::NOTIFICATION_TYPES as $type) {
            $entries[$type->value] = [
                'key' => $type->value, 'label' => $type->labelKey(), 'group' => $type->section()->labelKey(),
                'render' => fn () => $this->notification($type)->toMail($this->samples->user()),
            ];
        }
        foreach (['member-confirmation', 'student-rep-confirmation', 'feedback', 'reservation-batch'] as $key) {
            $entries[$key] = [
                'key' => $key, 'label' => 'mail_previews.scenarios.'.$key, 'group' => 'mail_previews.groups.other',
                'render' => fn () => $key === 'reservation-batch'
                    ? (new ReservationStatusChangedNotification($this->samples->reservationChanges(), $this->samples->user()))->toMail($this->samples->user())
                    : $this->samples->mailables()[$key],
            ];
        }

        return $entries;
    }

    public function index(): array
    {
        return array_values(array_map(function (array $entry): array {
            $subjects = [];
            foreach (['lt', 'en'] as $locale) {
                $subjects[$locale] = $this->renderer->safely(fn (): array => [
                    'subject' => $this->renderer->subject(($entry['render'])()),
                ], $locale)['subject'];
            }

            return [...array_diff_key($entry, ['render' => true]), 'subjects' => $subjects];
        }, $this->entries()));
    }

    public function render(string $key, string $locale = 'lt'): array
    {
        $entry = $this->entries()[$key] ?? null;
        abort_if($entry === null, 404);

        return $this->renderer->safely(fn (): array => $this->renderer->render(($entry['render'])()), $locale);
    }

    private function notification(NotificationType $type): BaseNotification
    {
        return collect($this->samples->notifications())->first(fn (BaseNotification $notification): bool => $notification->type() === $type)
            ?? throw new \LogicException('Missing mail preview sample: '.$type->value);
    }
}
