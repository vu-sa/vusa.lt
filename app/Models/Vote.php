<?php

namespace App\Models;

use App\Enums\VoteValue;
use App\Models\Pivots\AgendaItem;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Vote model - represents a single vote outcome within an agenda item.
 *
 * An agenda item can have one main vote and multiple additional votes.
 * Each vote tracks the student vote, decision outcome, and student benefit assessment.
 *
 * @property string $id
 * @property string $agenda_item_id
 * @property bool $is_main
 * @property string|null $title
 * @property string|null $student_vote
 * @property string|null $decision
 * @property string|null $student_benefit
 * @property string|null $note
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read AgendaItem $agendaItem
 * @property-read string|null $decision_label
 * @property-read bool $is_complete
 * @property-read string|null $student_benefit_label
 * @property-read string|null $student_vote_label
 * @property-read string $vote_alignment_status
 * @property-read bool $vote_matches
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote additional()
 * @method static \Database\Factories\VoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote main()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote query()
 *
 * @mixin \Eloquent
 */
class Vote extends Model
{
    use HasFactory, HasUlids, LogsActivity;

    protected $table = 'votes';

    protected $touches = ['agendaItem'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function newFactory(): Factory
    {
        return VoteFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    /**
     * Get the agenda item this vote belongs to.
     */
    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class, 'agenda_item_id', 'id');
    }

    /**
     * Check if this vote is complete (all three fields filled).
     */
    public function getIsCompleteAttribute(): bool
    {
        return ! empty($this->student_vote)
            && ! empty($this->decision)
            && ! empty($this->student_benefit);
    }

    /**
     * Check if student vote matches decision (vote alignment).
     */
    public function getVoteMatchesAttribute(): bool
    {
        return ! empty($this->student_vote)
            && ! empty($this->decision)
            && $this->student_vote === $this->decision;
    }

    /**
     * Calculate vote alignment status for this vote.
     *
     * @return string 'match', 'mismatch', 'incomplete', 'neutral'
     */
    public function getVoteAlignmentStatusAttribute(): string
    {
        $hasStudentVote = ! empty($this->student_vote);
        $hasDecision = ! empty($this->decision);

        // Neither vote nor decision recorded
        if (! $hasStudentVote && ! $hasDecision) {
            return 'neutral';
        }

        // Only one is filled - incomplete data
        if ($hasStudentVote xor $hasDecision) {
            return 'incomplete';
        }

        // Both filled - check if they match
        return $this->student_vote === $this->decision ? 'match' : 'mismatch';
    }

    /**
     * Get localized decision label.
     */
    public function getDecisionLabelAttribute(): ?string
    {
        if (empty($this->decision)) {
            return null;
        }

        $value = VoteValue::tryFrom($this->decision);

        return $value?->decisionLabel(app()->getLocale());
    }

    /**
     * Get localized student vote label.
     */
    public function getStudentVoteLabelAttribute(): ?string
    {
        if (empty($this->student_vote)) {
            return null;
        }

        $value = VoteValue::tryFrom($this->student_vote);

        return $value?->studentVoteLabel(app()->getLocale());
    }

    /**
     * Get localized student benefit label.
     */
    public function getStudentBenefitLabelAttribute(): ?string
    {
        if (empty($this->student_benefit)) {
            return null;
        }

        $value = VoteValue::tryFrom($this->student_benefit);

        return $value?->studentBenefitLabel(app()->getLocale());
    }

    /**
     * Scope to get only main votes.
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Scope to get only additional (non-main) votes.
     */
    public function scopeAdditional($query)
    {
        return $query->where('is_main', false);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set order on creation
        static::creating(function (Vote $vote) {
            if ($vote->order === 0 || $vote->order === null) {
                $maxOrder = static::where('agenda_item_id', $vote->agenda_item_id)->max('order') ?? -1;
                $vote->order = $maxOrder + 1;
            }
        });
    }
}
