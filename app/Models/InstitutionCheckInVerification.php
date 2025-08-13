<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionCheckInVerification extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(InstitutionCheckIn::class, 'check_in_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
