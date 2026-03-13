<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $planning_process_id
 * @property string $name
 * @property int $month
 * @property string|null $responsible_person
 * @property string $level
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PlanningProcess $planningProcess
 *
 * @method static \Database\Factories\PlanningActivityFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningActivity query()
 *
 * @mixin \Eloquent
 */
class PlanningActivity extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'order' => 'integer',
        ];
    }

    public function planningProcess(): BelongsTo
    {
        return $this->belongsTo(PlanningProcess::class);
    }
}
