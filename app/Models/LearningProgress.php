<?php

namespace App\Models;

use App\Enums\LearningProgressStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $progressable_type
 * @property string $progressable_id
 * @property string $user_id
 * @property LearningProgressStatus $status
 * @property Carbon $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model $progressable
 * @property-read User|null $user
 *
 * @method static \Database\Factories\LearningProgressFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningProgress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningProgress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LearningProgress query()
 *
 * @mixin \Eloquent
 */
#[Table('learning_progress')]
#[Fillable(['progressable_type', 'progressable_id', 'user_id', 'status', 'completed_at'])]
class LearningProgress extends Model
{
    use HasFactory;

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function progressable(): MorphTo
    {
        return $this->morphTo();
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
