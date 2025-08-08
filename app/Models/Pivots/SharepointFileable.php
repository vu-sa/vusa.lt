<?php

namespace App\Models\Pivots;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $sharepoint_file_id
 * @property string $fileable_type
 * @property string $fileable_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $fileable
 * @property-read Institution|null $institution
 * @property-read Meeting|null $meeting
 * @property-read Type|null $type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFileable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFileable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFileable query()
 *
 * @mixin \Eloquent
 */
class SharepointFileable extends MorphPivot
{
    use HasRelationships;

    protected $table = 'sharepoint_fileables';

    protected $guarded = [];

    // dutiable
    public function fileable()
    {
        return $this->morphTo();
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}
