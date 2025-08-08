<?php

namespace App\Models\Pivots;

use App\Models\ProgrammeBlock;
use App\Models\ProgrammeDay;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @property int $id
 * @property int $programme_day_id
 * @property string $elementable_type
 * @property int $elementable_id
 * @property int $order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProgrammeBlock> $blocks
 * @property-read ProgrammeDay|null $day
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $elementable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeElement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeElement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeElement query()
 *
 * @mixin \Eloquent
 */
class ProgrammeElement extends MorphPivot
{
    protected $table = 'programme_day_elements';

    protected $guarded = [];

    public function elementable()
    {
        return $this->morphTo();
    }

    public function day()
    {
        return $this->belongsTo(ProgrammeDay::class);
    }

    public function blocks()
    {
        return $this->hasMany(ProgrammeBlock::class);
    }
}
