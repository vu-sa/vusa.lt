<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * Notification sent when a user is assigned to a resource (reservation, task, etc.).
 *
 * Replaces the old UserAttachedToModel notification with standardized structure.
 */
class AssignedToResourceNotification extends BaseNotification
{
    /**
     * The user who made the assignment.
     *
     * @var array{modelClass: string, name: string, image?: string}
     */
    protected array $assigner;

    /**
     * The resource the user was assigned to.
     *
     * @var array{modelClass: string, name: string, url: string, id?: string}
     */
    protected array $resource;

    /**
     * Create a new notification instance.
     *
     * @param  array{modelClass: string, name: string, image?: string}  $assigner
     * @param  array{modelClass: string, name: string, url: string, id?: string}  $resource
     */
    public function __construct(array $assigner, array $resource)
    {
        $this->assigner = $assigner;
        $this->resource = $resource;
    }

    /**
     * Create from model and user.
     */
    public static function fromModel(Model $model, User $assigner): self
    {
        $objectName = $model->name ?? $model->title ?? __('resursas');

        $modelClass = class_basename(get_class($model));
        $routeName = Str::of($modelClass)->lcfirst()->plural().'.show';

        $resource = [
            'modelClass' => $modelClass,
            'name' => $objectName,
            'url' => route($routeName, $model->getKey()),
            'id' => $model->getKey(),
        ];

        $assignerData = [
            'modelClass' => 'User',
            'name' => $assigner->name,
            'image' => $assigner->profile_photo_path,
        ];

        return new self($assignerData, $resource);
    }

    public function category(): NotificationCategory
    {
        // Determine category based on resource type
        return match ($this->resource['modelClass']) {
            'Reservation', 'ReservationResource' => NotificationCategory::Reservation,
            'Task' => NotificationCategory::Task,
            'Meeting' => NotificationCategory::Meeting,
            default => NotificationCategory::User,
        };
    }

    public function title(object $notifiable): string
    {
        return __('notifications.assigned_to_resource_title', [
            'resource' => $this->resource['name'],
        ]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.assigned_to_resource_body', [
            'assigner' => $this->assigner['name'],
            'resource' => $this->resource['name'],
        ]);
    }

    public function url(): string
    {
        return $this->resource['url'] ?? route('dashboard');
    }

    public function icon(): string
    {
        return '🔗';
    }

    public function modelClass(): ?string
    {
        $mapping = [
            'Reservation' => 'RESERVATION',
            'ReservationResource' => 'RESERVATION_RESOURCE',
            'Task' => 'TASK',
            'Meeting' => 'MEETING',
        ];

        return $mapping[$this->resource['modelClass']] ?? null;
    }

    public function subject(): ?array
    {
        return $this->assigner;
    }

    public function object(): ?array
    {
        return $this->resource;
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view_resource'),
                'url' => $this->url(),
            ],
        ];
    }

    /**
     * Custom mail for better formatting.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->icon().' '.__('notifications.assigned_to_resource_title', ['resource' => $this->resource['name']]))
            ->markdown('emails.assigned-to-resource', [
                'assigner' => $this->assigner,
                'resource' => $this->resource,
            ]);
    }
}
