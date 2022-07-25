<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DutyUser extends Pivot
{
    protected $table = 'duties_users';

    protected $with = ['user', 'duty'];
    
    protected $casts = [
        'attributes' => 'object',
    ];

    public function duty()
    {
        return $this->belongsTo(Duty::class, 'duty_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
