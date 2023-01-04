<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InstitutionMeetingMatter extends Pivot
{
    use HasUlids;
    
    protected $guarded = [];

    public function matter()
    {
        return $this->belongsTo(InstitutionMatter::class);
    }

    public function meeting()
    {
        return $this->belongsTo(InstitutionMeeting::class);
    }
}
