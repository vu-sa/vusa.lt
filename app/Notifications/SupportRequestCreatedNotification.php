<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\SupportRequest;

/**
 * A new support request for the people who triage the queue.
 */
class SupportRequestCreatedNotification extends BaseNotification
{
    public function __construct(public SupportRequest $supportRequest) {}

    public function type(): NotificationType
    {
        return NotificationType::SupportRequestCreated;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.support_request_created_title', ['title' => $this->supportRequest->title]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.support_request_created_body', [
            'user' => $this->authorName(),
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
    public function context(object $notifiable): array
    {
        return $this->contextRows([
            'author' => $this->authorName(),
            'type' => $this->supportRequest->type->getTranslation('name', app()->getLocale()),
            'visibility' => $this->supportRequest->visibility->label(),
        ]);
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

    private function authorName(): string
    {
        return $this->supportRequest->creator->name ?? $this->supportRequest->reporter_name ?? __('Svečias');
    }
}
