<?php

namespace App\Models;

use App\Enums\LearningProgressStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $id
 * @property string $learning_step_id
 * @property string $user_id
 * @property LearningProgressStatus $status
 * @property Carbon $completed_at
 * @property-read LearningStep $step
 * @property-read User $user
 */
#[Table('learning_step_progress')]
#[Fillable(['learning_step_id', 'user_id', 'status', 'completed_at'])]
class LearningStepProgress extends Model
{
    use HasFactory;

    /** @return BelongsTo<LearningStep, $this> */
    public function step(): BelongsTo
    {
        return $this->belongsTo(LearningStep::class, 'learning_step_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['status' => LearningProgressStatus::class, 'completed_at' => 'datetime'];
    }
}
