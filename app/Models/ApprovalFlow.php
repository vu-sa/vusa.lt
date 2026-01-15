<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ApprovalFlow defines multi-step approval configurations.
 *
 * The `steps` JSON array contains step configurations:
 * [
 *   {
 *     "permission": "resources.update.padalinys",  // Required permission
 *     "required_count": 1,                         // Number of approvals needed
 *     "deadline_days": 3                           // Optional deadline per step
 *   },
 *   ...
 * ]
 *
 * @property string $id
 * @property string $name
 * @property string|null $flowable_type
 * @property string|null $flowable_id
 * @property array<array-key, mixed> $steps
 * @property bool $is_sequential
 * @property int|null $escalation_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $flowable
 * @property-read int $total_steps
 *
 * @method static \Database\Factories\ApprovalFlowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow global()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalFlow query()
 *
 * @mixin \Eloquent
 */
class ApprovalFlow extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'is_sequential' => 'boolean',
            'escalation_days' => 'integer',
        ];
    }

    /**
     * Get the flowable model (polymorphic, nullable for global flows).
     */
    public function flowable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get only global flows (not attached to specific models).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('flowable_type');
    }

    /**
     * Get the total number of steps in this flow.
     */
    public function getTotalStepsAttribute(): int
    {
        return count($this->steps ?? []);
    }

    /**
     * Get configuration for a specific step.
     *
     * @return array{permission?: string, required_count?: int, deadline_days?: int}|null
     */
    public function getStepConfig(int $step): ?array
    {
        $index = $step - 1; // Steps are 1-indexed

        return $this->steps[$index] ?? null;
    }

    /**
     * Get the required approval count for a step.
     */
    public function getRequiredCountForStep(int $step): int
    {
        return $this->getStepConfig($step)['required_count'] ?? 1;
    }

    /**
     * Get the permission required for a step.
     */
    public function getPermissionForStep(int $step): ?string
    {
        return $this->getStepConfig($step)['permission'] ?? null;
    }

    /**
     * Get the deadline days for a step.
     */
    public function getDeadlineDaysForStep(int $step): ?int
    {
        return $this->getStepConfig($step)['deadline_days'] ?? null;
    }

    /**
     * Check if this is a single-step flow.
     */
    public function isSingleStep(): bool
    {
        return $this->total_steps === 1;
    }

    /**
     * Check if this is a multi-step flow.
     */
    public function isMultiStep(): bool
    {
        return $this->total_steps > 1;
    }
}
