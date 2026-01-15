<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Pivots\ReservationResource;
use App\Models\User;

/**
 * Notification sent when a reservation resource status changes.
 *
 * Triggered on state transitions: Reserved, Rejected, Cancelled, Lent, Returned.
 */
class ReservationStatusChangedNotification extends BaseNotification
{
    protected ReservationResource $reservationResource;

    protected string $oldState;

    protected string $newState;

    protected ?User $changedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        ReservationResource $reservationResource,
        string $oldState,
        string $newState,
        ?User $changedBy = null
    ) {
        $this->reservationResource = $reservationResource;
        $this->oldState = $oldState;
        $this->newState = $newState;
        $this->changedBy = $changedBy;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Reservation;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.reservation_status_changed_title', [
            'status' => $this->getStateLabel($this->newState),
        ]);
    }

    public function body(object $notifiable): string
    {
        $reservationName = $this->reservationResource->reservation->name ?? __('Rezervacija');
        $resourceName = $this->reservationResource->resource->name ?? __('Resursas');

        $baseBody = '';
        if ($this->changedBy) {
            $baseBody = __('notifications.reservation_status_changed_body_with_user', [
                'reservation' => $reservationName,
                'resource' => $resourceName,
                'status' => $this->getStateLabel($this->newState),
                'user' => $this->changedBy->name,
            ]);
        } else {
            $baseBody = __('notifications.reservation_status_changed_body', [
                'reservation' => $reservationName,
                'resource' => $resourceName,
                'status' => $this->getStateLabel($this->newState),
            ]);
        }

        // Add task hint for states that create tasks
        $taskHint = $this->getTaskHint($resourceName);
        if ($taskHint) {
            $baseBody .= ' '.$taskHint;
        }

        return $baseBody;
    }

    /**
     * Get a task hint for states that automatically create tasks.
     */
    protected function getTaskHint(string $resourceName): ?string
    {
        return match ($this->newState) {
            'reserved' => __('notifications.reservation_task_hint_pickup', [
                'resource' => $resourceName,
                'date' => $this->reservationResource->start_time?->format('Y-m-d H:i'),
            ]),
            'lent' => __('notifications.reservation_task_hint_return', [
                'resource' => $resourceName,
                'date' => $this->reservationResource->end_time?->format('Y-m-d H:i'),
            ]),
            default => null,
        };
    }

    public function url(): string
    {
        return route('reservations.show', $this->reservationResource->reservation_id);
    }

    public function icon(): string
    {
        return match ($this->newState) {
            'reserved' => '✅',
            'rejected' => '❌',
            'cancelled' => '🚫',
            'lent' => '📦',
            'returned' => '↩️',
            default => '📅',
        };
    }

    public function modelClass(): ?string
    {
        return 'RESERVATION';
    }

    public function subject(): ?array
    {
        if (! $this->changedBy) {
            return null;
        }

        return [
            'modelClass' => 'User',
            'name' => $this->changedBy->name,
            'image' => $this->changedBy->profile_photo_path,
        ];
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Reservation',
            'name' => $this->reservationResource->reservation->name ?? __('Rezervacija'),
            'url' => $this->url(),
            'id' => $this->reservationResource->reservation_id,
        ];
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view_reservation'),
                'url' => $this->url(),
            ],
        ];
    }

    /**
     * Get a human-readable label for the state.
     */
    protected function getStateLabel(string $state): string
    {
        return match ($state) {
            'created' => __('state.status.created'),
            'reserved' => __('state.status.reserved'),
            'rejected' => __('state.status.rejected'),
            'cancelled' => __('state.status.cancelled'),
            'lent' => __('state.status.lent'),
            'returned' => __('state.status.returned'),
            default => $state,
        };
    }
}
