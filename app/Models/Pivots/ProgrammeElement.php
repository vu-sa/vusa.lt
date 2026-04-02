<?php

namespace App\Models\Pivots;

use App\Models\ProgrammeBlock;
use App\Models\ProgrammeDay;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $programme_day_id
 * @property string $elementable_type
 * @property int $elementable_id
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ProgrammeBlock> $blocks
 * @property-read ProgrammeDay|null $day
 * @property-read Model|\Eloquent $elementable
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
