<?php

namespace App\Models\Pivots;

use App\Actions\ProjectDutyResponsibilities;
use App\Models\Duty;
use App\Models\DutyType;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $duty_id
 * @property int $duty_type_id
 * @property-read Duty|null $duty
 * @property-read DutyType|null $type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyDutyType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyDutyType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyDutyType query()
 *
 * @mixin \Eloquent
 */
#[WithoutTimestamps]
class DutyDutyType extends Pivot
{
    protected static function booted(): void
    {
        // A type's responsibility grants reach its duties through the projection.
        static::created(fn (self $assignment) => ProjectDutyResponsibilities::forDuties([$assignment->duty_id]));
        static::deleted(fn (self $assignment) => ProjectDutyResponsibilities::forDuties([$assignment->duty_id]));
    }

    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class)->withTrashed();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DutyType::class, 'duty_type_id')->withTrashed();
    }
}
