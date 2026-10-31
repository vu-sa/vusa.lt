<?php

namespace App\Notifications;

use App\Models\Pivots\ReservationResource;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class ReservationStatusChangeBatch
{
    /** @var array<string, array{actor: User|null, changes: array<int, array{resource: ReservationResource, oldState: string, newState: string}>}> */
    private array $groups = [];

    public function add(ReservationResource $resource, string $oldState, string $newState, ?User $changedBy): void
    {
        $key = $resource->reservation_id.':'.($changedBy?->id ?? 'system');
        $this->groups[$key]['actor'] = $changedBy;
        $firstOldState = $this->groups[$key]['changes'][$resource->id]['oldState'] ?? $oldState;
        $this->groups[$key]['changes'][$resource->id] = [
            'resource' => $resource, 'oldState' => $firstOldState, 'newState' => $newState,
        ];
    }

    public function flush(): void
    {
        $groups = $this->groups;
        $this->groups = [];

        foreach ($groups as $group) {
            $changes = array_values($group['changes']);
            $reservation = $changes[0]['resource']->reservation;
            $recipients = $reservation->users->reject(fn (User $user): bool => $user->is($group['actor']));

            Notification::send($recipients, new ReservationStatusChangedNotification($changes, $group['actor']));
        }
    }
}
