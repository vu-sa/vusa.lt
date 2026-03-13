<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $planning_process_id
 * @property int $quarter
 * @property string $status_text
 * @property string $submitted_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PlanningProcess $planningProcess
 * @property-read User $submittedBy
 *
 * @method static \Database\Factories\PlanningMonitoringEntryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningMonitoringEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningMonitoringEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningMonitoringEntry query()
 *
 * @mixin \Eloquent
 */
class PlanningMonitoringEntry extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quarter' => 'integer',
        ];
    }

    public function planningProcess(): BelongsTo
    {
        return $this->belongsTo(PlanningProcess::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
