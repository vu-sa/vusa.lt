<?php

namespace App\Models;

use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $id
 * @property int $tenant_id
 * @property int $academic_year_start
 * @property string|null $moderator_user_id
 * @property int $current_stage
 * @property string|null $expectations_text
 * @property Carbon|null $expectations_submitted_at
 * @property string|null $meeting_1_notes
 * @property Carbon|null $meeting_1_date
 * @property string|null $meeting_2_notes
 * @property Carbon|null $meeting_2_date
 * @property array<int, array{date: string|null, notes: string|null}>|null $additional_meetings
 * @property string|null $selected_problem_id
 * @property string|null $goal_text
 * @property Carbon|null $goal_approved_at
 * @property Carbon|null $tip_approved_at
 * @property string|null $tip_approved_by
 * @property Carbon|null $mvp_approved_at
 * @property string|null $mvp_approved_by
 * @property string|null $reflection_text
 * @property Carbon|null $reflection_submitted_at
 * @property Carbon|null $locked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read User|null $moderator
 * @property-read Problem|null $selectedProblem
 * @property-read User|null $tipApprovedByUser
 * @property-read User|null $mvpApprovedByUser
 * @property-read Collection<int, PlanningActivity> $activities
 * @property-read Collection<int, PlanningMonitoringEntry> $monitoringEntries
 * @property-read Collection<int, Comment> $comments
 * @property-read MediaCollection<int, Media> $media
 *
 * @method static \Database\Factories\PlanningProcessFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningProcess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningProcess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningProcess onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningProcess query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningProcess withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningProcess withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PlanningProcess extends Model implements HasMedia
{
    use HasComments, HasFactory, HasUlids, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'academic_year_start' => 'integer',
            'current_stage' => 'integer',
            'expectations_submitted_at' => 'datetime',
            'meeting_1_date' => 'date',
            'meeting_2_date' => 'date',
            'additional_meetings' => 'array',
            'goal_approved_at' => 'datetime',
            'tip_approved_at' => 'datetime',
            'mvp_approved_at' => 'datetime',
            'reflection_submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('tip_document')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf'])
            ->useDisk('spatieMediaLibrary');

        $this
            ->addMediaCollection('mvp_document')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf'])
            ->useDisk('spatieMediaLibrary');

        $this
            ->addMediaCollection('tip_template')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->useDisk('spatieMediaLibrary');

        $this
            ->addMediaCollection('mvp_template')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->useDisk('spatieMediaLibrary');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_user_id');
    }

    public function selectedProblem(): BelongsTo
    {
        return $this->belongsTo(Problem::class, 'selected_problem_id');
    }

    public function tipApprovedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tip_approved_by');
    }

    public function mvpApprovedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mvp_approved_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PlanningActivity::class)->orderBy('order');
    }

    public function monitoringEntries(): HasMany
    {
        return $this->hasMany(PlanningMonitoringEntry::class)->orderBy('quarter');
    }

    /**
     * Get comments for a specific planning stage.
     *
     * @return MorphMany<Comment, $this>
     */
    public function stageComments(int $stage)
    {
        return $this->comments()->where('stage', $stage);
    }

    /**
     * Get users related to this planning process (for comment notifications).
     *
     * @return Collection<int, User>
     */
    public function getUsersAttribute(): Collection
    {
        return new Collection(array_filter([$this->moderator]));
    }

    public function isLocked(): bool
    {
        return ! is_null($this->locked_at);
    }

    public function isStageComplete(int $stage): bool
    {
        return match ($stage) {
            1 => ! is_null($this->expectations_submitted_at),
            2 => ! is_null($this->goal_approved_at),
            3 => ! is_null($this->tip_approved_at) && ! is_null($this->mvp_approved_at),
            4 => $this->activities()->exists(),
            5 => ! is_null($this->reflection_submitted_at),
            default => false,
        };
    }

    public function isFinished(): bool
    {
        return $this->current_stage > 5;
    }

    public function canAdvanceToStage(int $stage): bool
    {
        if ($stage <= 1 || $stage > 6) {
            return false;
        }

        return $this->isStageComplete($stage - 1);
    }

    /**
     * Automatically advance to the next stage if the current stage is complete.
     * Locks the process when advancing past stage 5.
     */
    public function advanceIfCurrentStageComplete(): bool
    {
        if ($this->isFinished() || ! $this->isStageComplete($this->current_stage)) {
            return false;
        }

        $nextStage = $this->current_stage + 1;
        $updateData = ['current_stage' => $nextStage];

        if ($nextStage > 5) {
            $updateData['locked_at'] = now();
        }

        $this->update($updateData);

        return true;
    }

    /**
     * Check if the planning coordinator needs to take action on this process.
     * Returns true when a goal or document is awaiting coordinator approval.
     */
    public function needsCoordinatorAction(): bool
    {
        if ($this->isFinished()) {
            return false;
        }

        // Goal submitted but not yet approved
        if ($this->goal_text && is_null($this->goal_approved_at)) {
            return true;
        }

        // TIP document uploaded but not approved
        if ($this->getFirstMedia('tip_document') && is_null($this->tip_approved_at)) {
            return true;
        }

        // MVP document uploaded but not approved
        if ($this->getFirstMedia('mvp_document') && is_null($this->mvp_approved_at)) {
            return true;
        }

        return false;
    }

    public function academicYearLabel(): string
    {
        return $this->academic_year_start.'-'.($this->academic_year_start + 1);
    }
}
