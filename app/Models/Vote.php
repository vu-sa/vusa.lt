<?php

namespace App\Models;

use App\Enums\VoteValue;
use App\Models\Pivots\AgendaItem;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Vote model - represents a single vote outcome within an agenda item.
 *
 * An agenda item can have one main vote and multiple additional votes.
 * Each vote tracks the student vote, decision outcome, and student benefit assessment.
 *
 * @property string $id
 * @property string $agenda_item_id
 * @property bool $is_main
 * @property bool $is_consensus
 * @property string|null $title
 * @property string|null $student_vote
 * @property string|null $decision
 * @property string|null $student_benefit
 * @property string|null $note
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read AgendaItem $agendaItem
 * @property-read mixed $decision_label
 * @property-read mixed $is_complete
 * @property-read mixed $student_benefit_label
 * @property-read mixed $student_vote_label
 * @property-read mixed $vote_alignment_status
 * @property-read mixed $vote_matches
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
#[Table(name: 'votes')]
#[Touches(['agendaItem'])]
#[Unguarded]
class Vote extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    /** @var list<string> */
    public $translatable = ['title', 'note'];

    /**
     * English vote labels are the exception, not the rule — see AgendaItem::getFallbackLocale().
     */
    public function getFallbackLocale(): string
    {
        return 'lt';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_consensus' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function newFactory(): Factory
    {
        return VoteFactory::new();
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
    protected function isComplete(): Attribute
    {
        return Attribute::make(get: fn () => ! empty($this->student_vote)
            && ! empty($this->decision)
            && ! empty($this->student_benefit));
    }

    /**
     * Check if student vote matches decision (vote alignment).
     */
    protected function voteMatches(): Attribute
    {
        return Attribute::make(get: fn () => ! empty($this->student_vote)
            && ! empty($this->decision)
            && $this->student_vote === $this->decision);
    }

    /**
     * Calculate vote alignment status for this vote.
     *
     * @return Attribute<'match'|'mismatch'|'incomplete'|'neutral', never>
     */
    protected function voteAlignmentStatus(): Attribute
    {
        return Attribute::make(get: function () {
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
        });
    }

    /**
     * Get localized decision label.
     */
    protected function decisionLabel(): Attribute
    {
        return Attribute::make(get: function () {
            if (empty($this->decision)) {
                return null;
            }
            $value = VoteValue::tryFrom($this->decision);

            return $value?->decisionLabel(app()->getLocale());
        });
    }

    /**
     * Get localized student vote label.
     */
    protected function studentVoteLabel(): Attribute
    {
        return Attribute::make(get: function () {
            if (empty($this->student_vote)) {
                return null;
            }
            $value = VoteValue::tryFrom($this->student_vote);

            return $value?->studentVoteLabel(app()->getLocale());
        });
    }

    /**
     * Get localized student benefit label.
     */
    protected function studentBenefitLabel(): Attribute
    {
        return Attribute::make(get: function () {
            if (empty($this->student_benefit)) {
                return null;
            }
            $value = VoteValue::tryFrom($this->student_benefit);

            return $value?->studentBenefitLabel(app()->getLocale());
        });
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
    #[\Override]
    protected static function boot()
    {
        parent::boot();

        // Set order on creation
        static::creating(function (Vote $vote): void {
            $order = $vote->getAttribute('order');

            if ($order === 0 || $order === null) {
                $maxOrder = static::where('agenda_item_id', $vote->agenda_item_id)->max('order') ?? -1;
                $vote->order = $maxOrder + 1;
            }
        });
    }
}
