<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property int|null $tenant_id
 * @property string $institution_id
 * @property string $user_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \App\Models\Institution $institution
 * @property-read \App\Models\Tenant|null $tenant
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\InstitutionCheckInFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionCheckIn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionCheckIn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionCheckIn query()
 *
 * @mixin \Eloquent
 */
class InstitutionCheckIn extends Model
{
    use HasFactory, HasUlids, LogsActivity, Searchable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if this check-in is currently active (within date range)
     */
    public function isActive(): bool
    {
        $now = Carbon::today();

        return $this->start_date <= $now && $this->end_date >= $now;
    }

    /**
     * Check if this check-in has expired
     */
    public function isExpired(): bool
    {
        return $this->end_date < Carbon::today();
    }

    public function toSearchableArray(): array
    {
        return [
            'institution_id' => $this->institution_id,
            'user_id' => $this->user_id,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
        ];
    }
}
