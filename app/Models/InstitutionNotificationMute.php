<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Represents a user muting notifications for an institution.
 *
 * This applies to both duty-based (automatic) and followed institutions.
 * Users can mute notifications from institutions they're associated with
 * through duties or that they explicitly follow.
 *
 * @property string $id
 * @property string $user_id
 * @property string $institution_id
 * @property \Illuminate\Support\Carbon $muted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Institution $institution
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionNotificationMute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionNotificationMute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionNotificationMute query()
 *
 * @mixin \Eloquent
 */
class InstitutionNotificationMute extends Pivot
{
    use HasFactory, HasUlids;

    protected $table = 'institution_notification_mutes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'muted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
