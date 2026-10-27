<?php

namespace App\Models\Pivots;

use App\Models\Duty;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A successor took over the predecessor's work from `effective_on` (its first day). The same
 * pair may link more than once on different days: merged, then split back into the same duties.
 *
 * @property int $id
 * @property string $predecessor_id
 * @property string $successor_id
 * @property Carbon $effective_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Duty|null $predecessor
 * @property-read Duty|null $successor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutySuccession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutySuccession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutySuccession query()
 *
 * @mixin \Eloquent
 */
#[Table(name: 'duty_successions')]
class DutySuccession extends Pivot
{
    public $incrementing = true;

    #[\Override]
    protected function casts(): array
    {
        return [
            'effective_on' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Duty, $this> */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Duty::class, 'predecessor_id');
    }

    /** @return BelongsTo<Duty, $this> */
    public function successor(): BelongsTo
    {
        return $this->belongsTo(Duty::class, 'successor_id');
    }
}
