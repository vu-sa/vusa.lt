<?php

namespace App\Models\Pivots;

use App\Models\Traits\HasDecisions;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ReservationResource extends Pivot
{
    use HasDecisions;

    protected $guarded = [];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
