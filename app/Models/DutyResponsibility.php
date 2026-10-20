<?php

namespace App\Models;

use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Transitional(responsibility-grants): the pre-grants table, kept for old activity log subjects;
 * remove with `duty_responsibilities` once ResponsibilityGrant has run on production.
 *
 * @property string $id
 * @property string $duty_id
 * @property string $responsibility
 * @property string $scope_type
 * @property string $scope_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Duty|null $duty
 * @property-read Model $scope
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyResponsibility newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyResponsibility newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyResponsibility query()
 *
 * @mixin \Eloquent
 */
#[Table(name: 'duty_responsibilities')]
#[Fillable(['duty_id', 'responsibility', 'scope_type', 'scope_id'])]
class DutyResponsibility extends Model
{
    use HasUlids, LogsModelActivity;

    /**
     * @return BelongsTo<Duty, $this>
     */
    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function scope(): MorphTo
    {
        return $this->morphTo('scope', 'scope_type', 'scope_id');
    }
}
