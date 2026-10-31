<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Pivots\ReservationResource;
use App\Models\User;

class ReservationStatusChangedNotification extends BaseNotification
{
    public function type(): NotificationType
    {
        return NotificationType::ReservationStatusChanged;
    }

    protected ReservationResource $reservationResource;

    protected string $newState;

    /** @param list<array{resource: ReservationResource, oldState: string, newState: string}> $changes */
    public function __construct(protected array $changes, protected ?User $changedBy = null)
    {
        if ($changes === []) {
            throw new \InvalidArgumentException('Reservation status changes cannot be empty.');
        }

        $this->reservationResource = $changes[0]['resource'];
        $this->newState = $changes[0]['newState'];
    }

    public function title(object $notifiable): string
    {
        if (count($this->changes) > 1) {
            return trans_choice('notifications.reservation_status_batch_title', count($this->changes), ['count' => count($this->changes)]);
        }

        return __('notifications.reservation_status_changed_title', [
            'status' => $this->getStateLabel($this->newState),
        ]);
    }

    public function body(object $notifiable): string
    {
        $reservationName = $this->reservationResource->reservation->name ?? __('Rezervacija');

        if (count($this->changes) > 1) {
            return $this->batchBody($reservationName);
        }

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

        $taskHint = $this->getTaskHint($resourceName);
        if ($taskHint) {
            $baseBody .= ' '.$taskHint;
        }

        return $baseBody;
    }

    private function batchBody(string $reservationName): string
    {
        $pairs = collect($this->changes)->map(fn (array $change): string => ($change['resource']->resource->name ?? __('Resursas')).' – '.$this->getStateLabel($change['newState']))->implode('; ');
        $body = __($this->changedBy ? 'notifications.reservation_status_batch_body_with_user' : 'notifications.reservation_status_batch_body', [
            'reservation' => $reservationName, 'changes' => $pairs, 'user' => $this->changedBy?->name,
        ]);
        foreach (['reserved', 'lent'] as $state) {
            $change = collect($this->changes)->firstWhere('newState', $state);
            if ($change === null) {
                continue;
            }
            $body .= ' '.match ($state) {
                'reserved' => __('notifications.reservation_batch_hint_reserved', ['date' => $change['resource']->start_time?->format('Y-m-d H:i')]),
                'lent' => __('notifications.reservation_batch_hint_lent', ['date' => $change['resource']->end_time?->format('Y-m-d H:i')]),
            };
        }

        return $body;
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

    #[\Override]
    public function icon(): string
    {
        if (count(array_unique(array_column($this->changes, 'newState'))) > 1) {
            return '📅';
        }

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

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_reservation'),
            'url' => $this->url(),
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
