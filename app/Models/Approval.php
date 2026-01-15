<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property string $approvable_type
 * @property string $approvable_id
 * @property string $user_id
 * @property ApprovalDecision $decision
 * @property int $step
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $approvable
 * @property-read User $user
 *
 * @method static \Database\Factories\ApprovalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval forStep(int $step)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval cancelled()
 *
 * @mixin \Eloquent
 */
class Approval extends Model
{
    use HasFactory, HasUlids, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
            'step' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    /**
     * Get the approvable model (polymorphic).
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the approval.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by step number.
     */
    public function scopeForStep($query, int $step)
    {
        return $query->where('step', $step);
    }

    /**
     * Scope to get only approved decisions.
     */
    public function scopeApproved($query)
    {
        return $query->where('decision', ApprovalDecision::Approved);
    }

    /**
     * Scope to get only rejected decisions.
     */
    public function scopeRejected($query)
    {
        return $query->where('decision', ApprovalDecision::Rejected);
    }

    /**
     * Scope to get only cancelled decisions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('decision', ApprovalDecision::Cancelled);
    }
}
