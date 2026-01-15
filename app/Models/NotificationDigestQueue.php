<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Queue item for email digest notifications.
 *
 * @property int $id
 * @property string $user_id
 * @property string $notification_class
 * @property string $category
 * @property array $data
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
class NotificationDigestQueue extends Model
{
    protected $table = 'notification_digest_queue';

    protected $fillable = [
        'user_id',
        'notification_class',
        'category',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Get the user this digest item belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notification category enum.
     */
    public function getNotificationCategory(): NotificationCategory
    {
        return NotificationCategory::from($this->category);
    }
}
