<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @property int $type_id
 * @property string $typeable_type
 * @property string $typeable_id
 * @property-read Type|null $type
 * @property-read Model|\Eloquent $typeable
 *
 * @method static \Database\Factories\TypeableFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Typeable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Typeable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Typeable query()
 *
 * @mixin \Eloquent
 */
#[WithoutTimestamps]
class Typeable extends MorphPivot
{
    use HasFactory;

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function typeable()
    {
        return $this->morphTo();
    }
}
