<?php

namespace App\Notifications;

use App\Contracts\Approvable;
use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification sent when an approval request is escalated due to being overdue.
 */
class ApprovalEscalationNotification extends BaseNotification
{
    protected Model $approvable;

    /**
     * @param  Model&Approvable  $approvable
     */
    public function __construct(Model $approvable)
    {
        $this->approvable = $approvable;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Reservation;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.approval_escalation_title');
    }

    public function body(object $notifiable): string
    {
        $displayName = $this->approvable instanceof Approvable
            ? $this->approvable->getApprovalDisplayName()
            : class_basename($this->approvable);

        return __('notifications.approval_escalation_body', [
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
            'id' => $this->approvable->id,
        ];
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_review'),
                'url' => $this->url(),
            ],
        ];
    }
}
