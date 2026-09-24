<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationUrgency;

/**
 * Someone else reserved a resource that sits in the recipient's unfinished reservation, and there
 * is no longer enough of it for their chosen time. Carts hold no capacity, so this is a heads-up.
 */
class ReservationDraftItemTakenNotification extends BaseNotification
{
    /**
     * @param  list<string>  $resourceNames
     */
    public function __construct(protected array $resourceNames, protected ?string $period = null) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Reservation;
    }

    public function urgency(): NotificationUrgency
    {
        return NotificationUrgency::Know;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.reservation_draft_item_taken_title');
    }

    public function body(object $notifiable): string
    {
        return trans_choice('notifications.reservation_draft_item_taken_body', count($this->resourceNames), [
            'resources' => implode(', ', $this->resourceNames),
        ]);
    }

    public function url(): string
    {
        return route('resources.index', ['cart' => 'open']);
    }

    #[\Override]
    public function icon(): string
    {
        return '🛒';
    }

    public function modelClass(): ?string
    {
        return 'RESERVATION';
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_review_reservation_draft'),
            'url' => $this->url(),
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        return $this->contextRows(['date' => $this->period]);
    }
}
