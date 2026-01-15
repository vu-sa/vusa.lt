<?php

namespace App\Notifications;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Enums\NotificationCategory;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification sent when an approval decision is made.
 */
class ApprovalDecisionNotification extends BaseNotification
{
    protected Approval $approval;

    protected Model $approvable;

    protected User $decisionMaker;

    /**
     * @param  Model&Approvable  $approvable
     */
    public function __construct(Approval $approval, Model $approvable, User $decisionMaker)
    {
        $this->approval = $approval;
        $this->approvable = $approvable;
        $this->decisionMaker = $decisionMaker;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Reservation;
    }

    public function title(object $notifiable): string
    {
        return match ($this->approval->decision) {
            ApprovalDecision::Approved => __('notifications.approval_approved_title'),
            ApprovalDecision::Rejected => __('notifications.approval_rejected_title'),
            ApprovalDecision::Cancelled => __('notifications.approval_cancelled_title'),
        };
    }

    public function body(object $notifiable): string
    {
        $displayName = $this->approvable instanceof Approvable
            ? $this->approvable->getApprovalDisplayName()
            : class_basename($this->approvable);

        return match ($this->approval->decision) {
            ApprovalDecision::Approved => __('notifications.approval_approved_body', [
                'item' => $displayName,
                'user' => $this->decisionMaker->name,
            ]),
            ApprovalDecision::Rejected => __('notifications.approval_rejected_body', [
                'item' => $displayName,
                'user' => $this->decisionMaker->name,
            ]),
            ApprovalDecision::Cancelled => __('notifications.approval_cancelled_body', [
                'item' => $displayName,
                'user' => $this->decisionMaker->name,
            ]),
        };
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
        return [
            'modelClass' => 'User',
            'name' => $this->decisionMaker->name,
            'image' => $this->decisionMaker->profile_photo_path,
        ];
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
