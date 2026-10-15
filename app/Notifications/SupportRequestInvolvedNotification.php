<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\SupportRequest;
use App\Models\User;

/**
 * Someone the reporter or a manager added because the problem concerns them too.
 */
class SupportRequestInvolvedNotification extends BaseNotification
{
    public function __construct(public SupportRequest $supportRequest, public User $actor) {}

    public function type(): NotificationType
    {
        return NotificationType::SupportRequestInvolved;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.support_request_involved_title', ['title' => $this->supportRequest->title]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.support_request_involved_body', [
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
