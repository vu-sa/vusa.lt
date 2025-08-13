<?php

namespace App\Models;

use App\States\InstitutionCheckIns\CheckInState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

class InstitutionCheckIn extends Model
{
    use HasFactory, HasUlids, HasStates, LogsActivity, Searchable;

    protected $guarded = [];

    protected $casts = [
        'until_date' => 'date',
        'checked_at' => 'datetime',
    'state' => CheckInState::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invalidatedBy(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'invalidated_by_meeting_id');
    }

    public function verifications()
    {
        return $this->hasMany(InstitutionCheckInVerification::class, 'check_in_id');
    }

    public function toSearchableArray()
    {
        return [
            'institution_id' => $this->institution_id,
            'user_id' => $this->user_id,
            'until_date' => optional($this->until_date)->toDateString(),
            'confidence' => $this->confidence,
            'state' => class_basename($this->state),
            'mode' => $this->mode,
        ];
    }
}
