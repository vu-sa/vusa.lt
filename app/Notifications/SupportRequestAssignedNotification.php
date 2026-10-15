<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\SupportRequest;
use App\Models\User;

/**
 * The person a manager made responsible for a support request.
 */
class SupportRequestAssignedNotification extends BaseNotification
{
    public function __construct(public SupportRequest $supportRequest, public User $actor) {}

    public function type(): NotificationType
    {
        return NotificationType::SupportRequestAssigned;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.support_request_assigned_title', ['title' => $this->supportRequest->title]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.support_request_assigned_body', [
            'user' => $this->actor->name,
            'title' => $this->supportRequest->title,
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

    #[\Override]
    public function subject(): ?array
    {
        return [
            'modelClass' => 'User',
            'name' => $this->actor->name,
            'image' => $this->actor->profile_photo_path,
        ];
    }

    #[\Override]
    public function object(): ?array
    {
        return [
            'modelClass' => 'SupportRequest',
            'name' => $this->supportRequest->title,
            'url' => $this->url(),
            'id' => $this->supportRequest->id,
        ];
    }
}
