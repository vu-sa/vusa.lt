<?php

namespace App\Models;

use Database\Factories\CadenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A term boundary. `institution_id` NULL is the global ladder every institution
 * inherits; a row with an institution overrides it for that body only (VU SA
 * Parlamentas starts in May, everyone else on 1 July).
 *
 * @property string $id
 * @property string|null $institution_id
 * @property string|null $start_meeting_id
 * @property string|null $end_meeting_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property-read string $label
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Institution|null $institution
 * @property-read Meeting|null $startMeeting
 * @property-read Meeting|null $endMeeting
 *
 * @method static Builder<static>|Cadence forInstitution(?string $institutionId)
 * @method static Builder<static>|Cadence globalLadder()
 * @method static Builder<static>|Cadence containing(string $date)
 * @method static CadenceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Cadence newModelQuery()
 * @method static Builder<static>|Cadence newQuery()
 * @method static Builder<static>|Cadence query()
 *
 * @mixin \Eloquent
 */
class Cadence extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    /** @var list<string> */
    protected $appends = ['label'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Derived, never stored: a term is identified by the years it spans, and a
     * hand-typed name only ever drifted from the dates beside it.
     */
    public function getLabelAttribute(): string
    {
        return implode('–', array_unique([$this->start_date->year, $this->end_date->year]));
    }

    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * The sitting a term opens at, when it has one. `withTrashed()` so a soft-deleted meeting
     * still names the boundary it set rather than leaving the row looking hand-typed.
     *
     * @return BelongsTo<Meeting, $this>
     */
    public function startMeeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'start_meeting_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Meeting, $this>
     */
    public function endMeeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'end_meeting_id')->withTrashed();
    }

    /**
     * @param  Builder<Cadence>  $query
     */
    public function scopeGlobalLadder(Builder $query): void
    {
        $query->whereNull('institution_id');
    }

    /**
     * @param  Builder<Cadence>  $query
     */
    public function scopeForInstitution(Builder $query, ?string $institutionId): void
    {
        $institutionId === null
            ? $query->whereNull('institution_id')
            : $query->where('institution_id', $institutionId);
    }

    /**
     * @param  Builder<Cadence>  $query
     */
    public function scopeContaining(Builder $query, string $date): void
    {
        $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date);
    }

    public function contains(Carbon $date): bool
    {
        return $date->betweenIncluded($this->start_date, $this->end_date);
    }
}
