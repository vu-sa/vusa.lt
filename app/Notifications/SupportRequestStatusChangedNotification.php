<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\SupportRequestStatus;
use App\Models\SupportRequest;
use App\Models\User;

class SupportRequestStatusChangedNotification extends BaseNotification
{
    public function __construct(
        public SupportRequest $supportRequest,
        public SupportRequestStatus $oldStatus,
        public SupportRequestStatus $newStatus,
        public ?User $updater = null
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::System;
    }

    public function title(object $notifiable): string
    {
        return __('Užklausos būsena atnaujinta');
    }

    public function body(object $notifiable): string
    {
        if ($this->updater) {
            return __(':user pakeitė užklausos „:title“ būseną į :status.', [
                'user' => $this->updater->name,
                'title' => $this->supportRequest->title,
                'status' => $this->newStatus->label(),
            ]);
        }

        return __('Užklausos „:title“ būsena pakeista į :status.', [
            'title' => $this->supportRequest->title,
            'status' => $this->newStatus->label(),
        ]);
    }

    public function url(): string
    {
        return route('supportRequests.show', $this->supportRequest->id);
    }
}
