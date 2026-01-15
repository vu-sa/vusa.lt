<?php

namespace App\Models;

use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property ActionType|null $action_type
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $taskable_type
 * @property string $taskable_id
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $color
 * @property-read string $icon
 * @property-read Model|\Eloquent $taskable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 *
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Task extends Model
{
    use HasFactory, HasRelationships, HasUlids, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'action_type' => ActionType::class,
            'metadata' => 'array',
        ];
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->tenants());
    }

    /**
     * Check if this task can be manually completed by users.
     */
    public function canBeManuallyCompleted(): bool
    {
        if ($this->action_type === null) {
            return true; // Legacy tasks without action_type are manual
        }

        return $this->action_type->canBeManuallyCompleted();
    }

    /**
     * Check if this task auto-completes based on system events.
     */
    public function isAutoCompletable(): bool
    {
        return ! $this->canBeManuallyCompleted();
    }

    /**
     * Check if this task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if this task is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->isCompleted() || $this->due_date === null) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Get progress information for tasks with metadata.
     * Returns null if no progress tracking, otherwise returns progress data.
     *
     * @return array{current: int, total: int, percentage: int}|null
     */
    public function getProgress(): ?array
    {
        if (! $this->metadata) {
            return null;
        }

        $total = $this->metadata['items_total'] ?? null;
        $completed = $this->metadata['items_completed'] ?? 0;

        if ($total === null || $total === 0) {
            return null;
        }

        return [
            'current' => $completed,
            'total' => $total,
            'percentage' => (int) round(($completed / $total) * 100),
        ];
    }

    /**
     * Update progress metadata and complete task if all items are done.
     */
    public function incrementProgress(int $amount = 1): bool
    {
        if (! $this->metadata || ! isset($this->metadata['items_total'])) {
            return false;
        }

        $metadata = $this->metadata;
        $metadata['items_completed'] = ($metadata['items_completed'] ?? 0) + $amount;

        $this->metadata = $metadata;
        $this->save();

        // Auto-complete if all items are done
        if ($metadata['items_completed'] >= $metadata['items_total']) {
            $this->completed_at = now();
            $this->save();

            return true; // Task was completed
        }

        return false; // Task still in progress
    }

    /**
     * Get the action type icon name for frontend display.
     */
    public function getIconAttribute(): string
    {
        return $this->action_type?->icon() ?? 'clipboard-check';
    }

    /**
     * Get the action type color for frontend display.
     */
    public function getColorAttribute(): string
    {
        return $this->action_type?->color() ?? 'zinc';
    }
}
