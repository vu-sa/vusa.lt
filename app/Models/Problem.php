<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property string $title
 * @property string $description
 * @property string|null $solution
 * @property int $tenant_id
 * @property string $created_by
 * @property string|null $responsible_user_id
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Tenant $tenant
 * @property-read \App\Models\User $createdBy
 * @property-read \App\Models\User|null $responsibleUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProblemCategory> $categories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 *
 * @method static \Database\Factories\ProblemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Problem extends Model
{
    use HasFactory, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'date',
        'resolved_at' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'solution' => $this->solution,
            'status' => $this->status,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProblemCategory::class);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved' && ! is_null($this->resolved_at);
    }

    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }
}
