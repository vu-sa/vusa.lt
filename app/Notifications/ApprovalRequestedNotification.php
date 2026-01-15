<?php

namespace App\Notifications;

use App\Contracts\Approvable;
use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification sent when approval is requested for an item.
 */
class ApprovalRequestedNotification extends BaseNotification
{
    protected Model $approvable;

    protected int $step;

    /**
     * @param  Model&Approvable  $approvable
     */
    public function __construct(Model $approvable, int $step = 1)
    {
        $this->approvable = $approvable;
        $this->step = $step;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Reservation;
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
            'id' => $this->approvable->id,
        ];
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view'),
                'url' => $this->url(),
            ],
        ];
    }
}
