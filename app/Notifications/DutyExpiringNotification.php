<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use Illuminate\Support\Carbon;

/**
 * Notification sent when a duty is expiring soon (30 days before end).
 *
 * Fixed at 30 days - sent once to remind the duty holder to prepare
 * for experience transfer to their successor.
 *
 * NOTE: If you're receiving this notification unexpectedly, it might indicate
 * a misconfiguration in the duty end dates. Please check the dutiable records.
 */
class DutyExpiringNotification extends BaseNotification
{
    protected Duty $duty;

    protected Dutiable $dutiable;

    protected int $daysUntilExpiry;

    /**
     * Create a new notification instance.
     */
    public function __construct(Duty $duty, Dutiable $dutiable, int $daysUntilExpiry = 30)
    {
        $this->duty = $duty;
        $this->dutiable = $dutiable;
        $this->daysUntilExpiry = $daysUntilExpiry;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Duty;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.duty_expiring_title', ['days' => $this->daysUntilExpiry]);
    }

    public function body(object $notifiable): string
    {
        $endDate = Carbon::parse($this->dutiable->end_date)->format('Y-m-d');

        return __('notifications.duty_expiring_body', [
            'duty' => $this->duty->name,
            'date' => $endDate,
        ]);
    }

    public function url(): string
    {
        return route('duties.show', $this->duty->id);
    }

    public function icon(): string
    {
        return '🔔';
    }

    public function modelClass(): ?string
    {
        return 'DUTY';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Duty',
            'name' => $this->duty->name,
            'url' => $this->url(),
            'id' => $this->duty->id,
        ];
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view_duty'),
                'url' => $this->url(),
            ],
        ];
    }

    /**
     * Duty expiry notifications are important reminders and should not be digested.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
