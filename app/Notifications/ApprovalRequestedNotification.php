<?php

namespace App\Notifications;

use App\Contracts\Approvable;
use App\Enums\NotificationType;
use App\Notifications\Concerns\NamesObjectInTitle;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification sent when approval is requested for an item.
 */
class ApprovalRequestedNotification extends BaseNotification
{
    use NamesObjectInTitle;

    public function type(): NotificationType
    {
        return NotificationType::ApprovalRequested;
    }

    public function __construct(protected Model $approvable, protected int $step = 1) {}

    public function title(object $notifiable): string
    {
        return __('notifications.approval_requested_title', ['item' => $this->object()['name']]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.approval_requested_body');
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
