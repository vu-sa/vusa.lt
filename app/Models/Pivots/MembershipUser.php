<?php

namespace App\Models\Pivots;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
