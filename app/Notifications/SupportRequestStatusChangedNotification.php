<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Enums\SupportRequestStatus;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\Concerns\NamesObjectInTitle;

class SupportRequestStatusChangedNotification extends BaseNotification
{
    use NamesObjectInTitle;

    public function type(): NotificationType
    {
        return NotificationType::SupportRequestStatusChanged;
    }

    public function __construct(
        public SupportRequest $supportRequest,
        public SupportRequestStatus $oldStatus,
        public SupportRequestStatus $newStatus,
        public ?User $updater = null
    ) {}

    public function title(object $notifiable): string
    {
        return __('notifications.support_request_status_changed_title', ['title' => $this->supportRequest->title]);
    }

    public function body(object $notifiable): string
    {
        if ($this->updater) {
            return __('notifications.support_request_status_changed_body_with_user', [
                'user' => $this->updater->name,
                'status' => $this->newStatus->label(),
            ]);
        }

        return __('notifications.support_request_status_changed_body', [
            'status' => $this->newStatus->label(),
        ]);
    }

    public function url(): string
    {
        return route('supportRequests.show', $this->supportRequest->id);
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return ['label' => __('notifications.action_open_support_request'), 'url' => $this->url()];
    }
}
