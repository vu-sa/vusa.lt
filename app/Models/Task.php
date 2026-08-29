<?php

namespace App\Models;

use App\Actions\ResolveTaskAudience;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property ActionType|null $action_type
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $due_date
 * @property string $taskable_type
 * @property string $taskable_id
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read mixed $color
 * @property-read mixed $icon
 * @property-read Model|\Eloquent $taskable
 * @property-read Collection<int, Tenant> $tenants
 * @property-read Collection<int, User> $users
 * @property-read int|null $tenants_count
 *
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task incomplete()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
class Task extends Model
{
    use HasFactory, HasRelationships, HasUlids;

    #[\Override]
    protected static function booted(): void
    {
        // task_user.task_id is a RESTRICT foreign key, so a task with assignees cannot be
        // deleted until its pivot rows go — without this, deleting a real task throws.
        static::deleting(function (Task $task): void {
            $task->users()->detach();
        });
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'action_type' => ActionType::class,
            'metadata' => 'array',
        ];
    }

    /**
     * Scope to incomplete tasks (not yet completed).
     */
    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * The models a task may be filed against.
     *
     * `taskable_type` arrives from request input, so it must be resolved through an allowlist
     * rather than trusted — see StoreTaskRequest.
     *
     * @var list<class-string<Model>>
     */
    /**
     * The morph aliases a task may hang off — what `taskable_type` stores and what
     * StoreTaskRequest accepts from the frontend.
     *
     * @var list<string>
     */
    public const TASKABLE_TYPES = [
        'institution',
        'meeting',
        'reservation',
        'user',
    ];

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Assignees who should still hear about this task — see {@see ResolveTaskAudience}.
     * Assignment itself is left alone; only the notification audience narrows.
     *
     * @return SupportCollection<int, User>
     */
    public function notifiableUsers(): SupportCollection
    {
        return ResolveTaskAudience::execute($this);
    }

    public function tenants(): HasManyDeep
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
    protected function icon(): Attribute
    {
        return Attribute::make(get: fn () => $this->action_type?->icon() ?? 'clipboard-check');
    }

    /**
     * Get the action type color for frontend display.
     */
    protected function color(): Attribute
    {
        return Attribute::make(get: fn () => $this->action_type?->color() ?? 'zinc');
    }
}
