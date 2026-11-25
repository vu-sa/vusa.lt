<?php

namespace App\Services\MailPreview;

use App\Enums\NotificationType;
use App\Mail\NotificationDigest;
use App\Notifications\ReservationStatusChangedNotification;
use Closure;

class MailPreviewCatalog
{
    public function __construct(private readonly MailPreviewSamples $samples, private readonly MailPreviewRenderer $renderer) {}

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
        foreach ($this->samples->notificationFactories() as $key => $factory) {
            $type = NotificationType::from($key);
            $entries[$key] = [
                'key' => $key, 'label' => $type->labelKey(), 'group' => $type->section()->labelKey(),
                'render' => fn () => $factory()->toMail($this->samples->user()),
            ];
        }
        foreach (['member-confirmation', 'student-rep-confirmation', 'feedback', 'reservation-batch'] as $key) {
            $entries[$key] = [
                'key' => $key, 'label' => 'mail_previews.scenarios.'.$key, 'group' => 'mail_previews.groups.other',
                'render' => fn () => $key === 'reservation-batch'
                    ? new ReservationStatusChangedNotification($this->samples->reservationChanges(), $this->samples->user())->toMail($this->samples->user())
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
}
