<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\InstitutionActivityRequest;

/**
 * Someone asked "Ar vyko posėdis?" says the institution is not theirs, so the secretaries or
 * representatives the email was resolved from need a look.
 */
class InstitutionActivityNotMineNotification extends BaseNotification
{
    public function __construct(private readonly InstitutionActivityRequest $request) {}

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return InstitutionActivityRequest::query()->whereKey($this->request->id)->whereHas('institution')->whereHas('recipient')->exists();
    }

    public function type(): NotificationType
    {
        return NotificationType::InstitutionActivityNotMine;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.activity_not_mine_title', ['institution' => $this->request->institution->name]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.activity_not_mine_body', [
            'name' => $this->request->recipient->name,
            'institution' => $this->request->institution->name,
        ]);
    }

    public function url(): string
    {
        return route('institutions.show', $this->request->institution);
    }

    public function modelClass(): ?string
    {
        return 'Institution';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Institution',
            'name' => $this->request->institution->name,
            'url' => $this->url(),
            'id' => $this->request->institution->id,
        ];
    }

    #[\Override]
    public function subject(): ?array
    {
        return [
            'modelClass' => 'User',
            'name' => $this->request->recipient->name,
        ];
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return ['label' => __('notifications.action_open_institution'), 'url' => $this->url()];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        return $this->contextRows([
            'institution' => $this->request->institution->name,
            'answered_by' => $this->request->recipient->name,
        ]);
    }
}
