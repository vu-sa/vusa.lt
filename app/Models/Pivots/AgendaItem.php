<?php

namespace App\Models\Pivots;

use App\Models\Institution;
use App\Models\Meeting;
use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $meeting_id
 * @property string|null $matter_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $title
 * @property string|null $description
 * @property string|null $student_vote
 * @property string|null $decision
 * @property string|null $student_benefit
 * @property string|null $start_time
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Institution> $institutions
 * @property-read Meeting $meeting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 *
 * @method static \Database\Factories\AgendaItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItem query()
 *
 * @mixin \Eloquent
 */
class AgendaItem extends Pivot
{
    use HasFactory, HasRelationships, HasUlids, LogsActivity;

    protected $table = 'agenda_items';

    protected $touches = ['meeting'];

    public $incrementing = true;

    protected static function newFactory(): Factory
    {
        return AgendaItemFactory::new();
    }

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function meeting(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function institutions()
    {
        return $this->hasManyDeepFromRelations($this->meeting(), (new Meeting)->institutions());
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->tenant());
    }
}
