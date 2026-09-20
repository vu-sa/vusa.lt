<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationUrgency;

/**
 * "Tavo prieigos pasikeitė" (U14): one notice per person per day listing the duty terms that
 * began or ended, so a whole-unit term change is one message rather than one per duty.
 */
class AccessChangedNotification extends BaseNotification
{
    /** More rows than this stop being a glance; the history on *Mano rolės* has the rest. */
    private const int MAX_ROWS = 4;

    /**
     * @param  list<array{kind: string, dutyName: string, institutionName: string|null, date: string, effectiveOn: string, isExOfficio: bool}>  $changes
     */
    public function __construct(
        private readonly array $changes,
        private readonly string $date,
    ) {}

    /** Identifies one day's notice, so a re-run of the command does not repeat it. */
    public static function key(string $date): string
    {
        return 'access-change-'.$date;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Duty;
    }

    public function urgency(): NotificationUrgency
    {
        return NotificationUrgency::Know;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.access_changed_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.access_changed_body');
    }

    public function url(): string
    {
        return route('profile.roles');
    }

    #[\Override]
    public function modelClass(): ?string
    {
        return 'DUTY';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Duty',
            'name' => __('notifications.access_changed_title'),
            'url' => $this->url(),
            'id' => self::key($this->date),
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        return collect($this->changes)
            ->take(self::MAX_ROWS)
            ->map(fn (array $change): array => [
                'label' => __('notifications.context.access_'.$change['kind'], ['date' => $change['effectiveOn']]),
                'value' => $change['institutionName'] === null
                    ? $change['dutyName']
                    : $change['dutyName'].' · '.$change['institutionName'],
            ])
            ->values()
            ->all();
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_access'),
            'url' => $this->url(),
        ];
    }
}
