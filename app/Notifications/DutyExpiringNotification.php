<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Notifications\Concerns\NamesObjectInTitle;
use Illuminate\Support\Carbon;

/** A reminder to prepare the handover before the last day in office. */
class DutyExpiringNotification extends BaseNotification
{
    use NamesObjectInTitle;

    public function type(): NotificationType
    {
        return NotificationType::DutyExpiring;
    }

    public function __construct(protected Duty $duty, protected Dutiable $dutiable, protected int $daysUntilExpiry = 30) {}

    public function title(object $notifiable): string
    {
        return __('notifications.duty_expiring_title', ['duty' => $this->duty->name]);
    }

    public function body(object $notifiable): string
    {
        $endDate = Carbon::parse($this->dutiable->end_date)->format('Y-m-d');

        return __('notifications.duty_expiring_body', [
            'date' => $endDate,
        ]);
    }

    public function url(): string
    {
        return route('duties.show', $this->duty->id);
    }

    #[\Override]
    public function icon(): string
    {
        return '🔔';
    }

    public function modelClass(): ?string
    {
        return 'DUTY';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Duty',
            'name' => $this->duty->name,
            'url' => $this->url(),
            'id' => $this->duty->id,
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        return $this->contextRows([
            'institution' => $this->duty->institution?->name,
        ]);
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_duty'),
            'url' => $this->url(),
        ];
    }
}
