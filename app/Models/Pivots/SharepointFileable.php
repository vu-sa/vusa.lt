<?php

namespace App\Models\Pivots;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

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
