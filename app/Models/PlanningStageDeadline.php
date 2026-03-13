<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $academic_year_start
 * @property int $stage
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static \Database\Factories\PlanningStageDeadlineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningStageDeadline newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningStageDeadline newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningStageDeadline query()
 *
 * @mixin \Eloquent
 */
class PlanningStageDeadline extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'academic_year_start' => 'integer',
            'stage' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }
}
