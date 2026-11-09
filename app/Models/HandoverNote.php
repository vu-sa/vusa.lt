<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $duty_id
 * @property string $user_id
 * @property string|null $dutiable_id
 * @property string|null $cadence_id
 * @property string|null $situation
 * @property string|null $open_issues
 * @property string|null $people
 * @property string|null $resources
 * @property string|null $advice
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Duty $duty
 * @property-read User $author
 * @property-read Dutiable|null $dutiable
 * @property-read Cadence|null $cadence
 */
#[Fillable(['duty_id', 'user_id', 'dutiable_id', 'cadence_id', 'situation', 'open_issues', 'people', 'resources', 'advice'])]
class HandoverNote extends Model
{
    use HasFactory, HasUlids, LogsModelActivity;

    /** @return BelongsTo<Duty, $this> */
    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Dutiable, $this> */
    public function dutiable(): BelongsTo
    {
        return $this->belongsTo(Dutiable::class);
    }

    /** @return BelongsTo<Cadence, $this> */
    public function cadence(): BelongsTo
    {
        return $this->belongsTo(Cadence::class);
    }
}
