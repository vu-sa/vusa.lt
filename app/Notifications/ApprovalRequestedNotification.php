<?php

namespace App\Notifications;

use App\Contracts\Approvable;
use App\Enums\NotificationCategory;
use App\Enums\NotificationUrgency;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification sent when approval is requested for an item.
 */
class ApprovalRequestedNotification extends BaseNotification
{
    public function __construct(protected Model $approvable, protected int $step = 1) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Reservation;
    }

    public function urgency(): NotificationUrgency
    {
        return NotificationUrgency::Act;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.approval_requested_title');
    }

    public function body(object $notifiable): string
    {
        $displayName = $this->approvable instanceof Approvable
            ? $this->approvable->getApprovalDisplayName()
            : class_basename($this->approvable);

        return __('notifications.approval_requested_body', [
            'item' => $displayName,
        ]);
    }

    public function url(): string
    {
        if ($this->approvable instanceof Approvable) {
            return $this->approvable->getApprovalUrl();
        }

        return route('dashboard');
    }

    public function modelClass(): ?string
    {
        return 'RESERVATION';
    }

    public function subject(): ?array
    {
        return null;
    }

    public function object(): ?array
    {
        $displayName = $this->approvable instanceof Approvable
            ? $this->approvable->getApprovalDisplayName()
            : class_basename($this->approvable);

        return [
            'modelClass' => class_basename($this->approvable),
            'name' => $displayName,
            'url' => $this->url(),
            'id' => $this->approvable->getKey(),
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        return $this->contextRows([
            'object' => $this->object()['name'] ?? null,
            'step' => $this->step > 1 ? $this->step : null,
        ]);
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view'),
            'url' => $this->url(),
        ];
    }
}
