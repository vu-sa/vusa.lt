<?php

namespace App\Models\Pivots;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property string $membership_id
 * @property string $user_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Membership $membership
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipUser query()
 *
 * @mixin \Eloquent
 */
class MembershipUser extends Pivot
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
